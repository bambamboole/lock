<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Context;

use function Tests\Helpers\realmRoute;

test('a new realm gets its own signing key and serves it from its own JWKS', function () {
    $realm = app(CreateRealm::class)->handle('Test Realm', 'test.lock.test', 'test-realm');

    $kid = SigningKey::query()->inRealm($realm->slug)->sole()->kid;

    $this->getJson(realmRoute($realm, 'oidc.jwks'))
        ->assertOk()
        ->assertJsonCount(1, 'keys')
        ->assertJsonPath('keys.0.kid', $kid);
});

test('a realm identifier cannot change once created', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);

    expect(fn () => $realm->update(['slug' => 'renamed']))->toThrow(LogicException::class);
});

test('an address identifies a different person in each realm', function () {
    $one = Realm::factory()->create();
    $two = Realm::factory()->create();

    $here = User::factory()->for($one)->create(['email' => 'ada@example.com']);
    $there = User::factory()->for($two)->create(['email' => 'ada@example.com']);

    expect($here->is($there))->toBeFalse()
        ->and($here->realm_id)->not->toEqual($there->realm_id);
});

test('an address cannot repeat inside one realm', function () {
    $realm = Realm::factory()->create();

    User::factory()->for($realm)->create(['email' => 'ada@example.com']);

    expect(fn () => User::factory()->for($realm)->create(['email' => 'ada@example.com']))
        ->toThrow(QueryException::class);
});

test('the admin realm cannot be deleted', function () {
    expect(fn () => app(DeleteRealm::class)->handle(Realm::master()))->toThrow(LogicException::class);

    $this->assertDatabaseHas('realms', ['slug' => Realm::master()->slug]);
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
