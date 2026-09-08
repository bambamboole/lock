<?php
declare(strict_types=1);

use App\Realms\Models\Activity;
use App\Realms\Models\Realm;
use App\Shared\Database\Scopes\RealmScope;
use App\Shared\Exceptions\MissingRealmContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\ScopedRecord;
use Tests\Fixtures\SharedRecord;

use function Tests\Helpers\assumeNotNull;

beforeEach(function () {
    ScopedRecord::createTable();
    SharedRecord::createTable();
});

it('stamps the current realm on create without the model asking for it', function () {
    $realm = Realm::factory()->create();

    $record = $realm->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'Widget']));

    expect($record->realm_id)->toBe($realm->id)
        ->and($record->realm->is($realm))->toBeTrue();
});

it('hides another realm rows from every query', function () {
    $realmA = Realm::factory()->create();
    $realmB = Realm::factory()->create();

    $realmA->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'A']));
    $realmB->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'B']));

    expect($realmA->runAsCurrent(fn () => ScopedRecord::query()->pluck('name')->all()))->toBe(['A'])
        ->and($realmB->runAsCurrent(fn () => ScopedRecord::query()->pluck('name')->all()))->toBe(['B'])
        ->and(ScopedRecord::query()->withoutGlobalScope(RealmScope::class)->count())->toBe(2);
});

it('refuses to query or create without a current realm', function () {
    Context::forget('realm_id');

    expect(fn () => ScopedRecord::query()->count())->toThrow(MissingRealmContext::class)
        ->and(fn () => ScopedRecord::query()->create(['name' => 'Orphan']))->toThrow(MissingRealmContext::class);
});

it('restores the previous realm context after a nested run', function () {
    $realmA = Realm::factory()->create();
    $realmB = Realm::factory()->create();

    Context::forget('realm_id');

    $inner = $realmA->runAsCurrent(function () use ($realmA, $realmB): array {
        $nested = $realmB->runAsCurrent(fn () => Context::get('realm_id'));

        expect(Context::get('realm_id'))->toBe($realmA->id);

        return [$nested];
    });

    expect($inner)->toBe([$realmB->id])
        ->and(Context::get('realm_id'))->toBeNull();
});

it('shows shared rows alongside the current realm under GlobalOrRealmScope', function () {
    $realmA = Realm::factory()->create();
    $realmB = Realm::factory()->create();

    $realmA->runAsCurrent(fn () => SharedRecord::query()->create(['name' => 'A']));
    $realmB->runAsCurrent(fn () => SharedRecord::query()->create(['name' => 'B']));
    new SharedRecord(['name' => 'Shared'])->asGlobal()->save();

    expect($realmA->runAsCurrent(fn () => SharedRecord::query()->pluck('name')->all()))
        ->toEqualCanonicalizing(['A', 'Shared'])
        ->and(SharedRecord::query()->pluck('name')->all())->toBe(['Shared']);
});

it('refuses to create a shared-scoped row without a realm unless it is marked global', function () {
    Context::forget('realm_id');

    expect(fn () => SharedRecord::query()->create(['name' => 'Implicit']))->toThrow(MissingRealmContext::class);
});

it('answers an api request without a realm context with a 403 instead of a 500', function () {
    Context::forget('realm_id');

    Route::middleware('api')->get('/api/scoping-probe', fn () => ScopedRecord::query()->count());

    $this->getJson('/api/scoping-probe')
        ->assertForbidden()
        ->assertJson(['message' => __('realm.api.no-realm-context')]);
});

it('stamps the realm on the audit trail of a scoped record', function () {
    $realm = Realm::factory()->create();

    $realm->runAsCurrent(function () {
        $record = ScopedRecord::query()->create(['name' => 'Widget']);
        $record->update(['name' => 'Gadget']);
    });

    $activity = Activity::query()->where('subject_type', ScopedRecord::class)->where('event', 'updated')->sole();
    $changes = $activity->attribute_changes;
    assumeNotNull($changes);

    expect($activity->realm_id)->toBe($realm->id)
        ->and($activity->log_name)->toBe('realm')
        ->and($changes->get('attributes'))->toBe(['name' => 'Gadget']);
});
