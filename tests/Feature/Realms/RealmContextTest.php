<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Activity;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Ui\Tables\RealmMembersTable;
use Illuminate\Support\Facades\Context;
use Lattice\Core\Services\ContextResolutions;
use Lattice\Core\Services\ContextResolvers;

use function Tests\Helpers\realmMember;

test('web requests share the current realm in context for queued jobs', function () {
    $user = User::factory()->create();
    Context::flush();

    $this->actingAs($user)->get('/dashboard');

    expect(Context::get('realm_id'))->toBe($user->realm->id);
});

test('the realm can be resolved back from context', function () {
    $realm = Realm::factory()->create();
    Context::flush();

    expect(Realm::fromContext())->toBeNull();

    $realm->makeCurrent();

    expect(Realm::fromContext()?->id)->toBe($realm->id);
});

test('making a realm current puts it in the context', function () {
    [, $realm] = realmMember(RolePreset::Member);
    Context::flush();

    $realm->makeCurrent();

    expect(Context::get('realm_id'))->toBe($realm->id);
});

test('bound realm models map to the realm page context', function () {
    $realm = Realm::factory()->create();

    expect(app(ContextResolvers::class)->keyForModel($realm))->toBe('realm');
});

test('lattice context resolvers scope records through their realm', function () {
    [$user, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->memberRole();
    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'invited_by' => $user->id,
    ]);
    $activity = Activity::query()->forceCreate([
        'log_name' => 'realm',
        'description' => 'Realm updated',
        'event' => 'realm.updated',
        'realm_id' => $realm->id,
    ]);
    $context = [
        'realm' => $realm->slug,
        'member' => $user->id,
        'invitation' => $invitation->code,
        'role' => $role->id,
        'activity' => $activity->id,
        'user' => $user->id,
    ];

    $this->actingAs($user);
    request()->setUserResolver(fn (): User => $user);
    $resolutions = app(ContextResolutions::class);

    expect(data_get($resolutions->resolve('realm', $context['realm'], $context), 'id'))->toBe($realm->id)
        ->and(data_get($resolutions->resolve('member', $context['member'], $context), 'id'))->toBe($user->id)
        ->and(data_get($resolutions->resolve('invitation', $context['invitation'], $context), 'id'))->toBe($invitation->id)
        ->and(data_get($resolutions->resolve('role', $context['role'], $context), 'id'))->toBe($role->id)
        ->and(data_get($resolutions->resolve('activity', $context['activity'], $context), 'id'))->toBe($activity->id)
        ->and(data_get($resolutions->resolve('user', $context['user'], $context), 'id'))->toBe($user->id);
});

test('a signed endpoint makes the request realm current', function () {
    [$user, $realm] = realmMember(RolePreset::Owner);
    Context::flush();

    $this->actingAs($user)->loadTable(RealmMembersTable::class, context: ['realm' => $realm->slug])->assertOk();

    expect(Context::get('realm_id'))->toBe($realm->id);
});
