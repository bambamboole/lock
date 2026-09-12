<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Roles\Models\Role;
use App\Roles\Ui\Actions\CreateRoleAction;
use App\Roles\Ui\Actions\DeleteRoleAction;
use App\Roles\Ui\Actions\UpdateRoleAction;
use App\Roles\Ui\Actions\UpdateUserRoles;
use App\Roles\Ui\Tables\RolesTable;
use App\Shared\Audit\Models\Activity;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Roles\Contracts\SyncsUserRoles;
use App\Shared\Roles\Enums\RoleAuditEvent;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

test('a roles-write admin creates a realm role and it is recorded', function () {
    $this->actingAs(globalAdmin())->callAction(CreateRoleAction::class, [
        'name' => 'member',
        'description' => '  Standard access  ',
    ], ['realm' => 'acme'])->assertOk();

    $role = $this->realm->roles()->sole();

    expect($role->name)->toBe('member')
        ->and($role->description)->toBe('Standard access')
        ->and($role->realm_id)->toBe($this->realm->id);

    $activity = Activity::forRealm($this->realm)->where('event', RoleAuditEvent::RoleCreated->eventName())->sole();

    expect($activity->subject_id)->toBe($role->id)
        ->and($activity->getProperty('realm'))->toBe('acme')
        ->and($activity->getProperty('name'))->toBe('member');
});

test('a realm role name is unique per realm but free in another', function () {
    Role::factory()->for($this->realm)->create(['name' => 'member']);

    $this->actingAs(globalAdmin());
    $this->callAction(CreateRoleAction::class, ['name' => 'member'], ['realm' => 'acme'])->assertInvalid(['name']);
    $this->callAction(CreateRoleAction::class, ['name' => 'member'], ['realm' => 'globex'])->assertOk();

    expect(Role::query()->where('name', 'member')->whereIn('realm_id', [$this->realm->id, $this->other->id])->count())->toBe(2);
});

test('a realm role name is rejected when it is not token safe', function (string $name) {
    $this->actingAs(globalAdmin())
        ->callAction(CreateRoleAction::class, ['name' => $name], ['realm' => 'acme'])
        ->assertInvalid(['name']);

    expect($this->realm->roles()->exists())->toBeFalse();
})->with([
    'whitespace' => 'realm admin',
    'leading punctuation' => '-admin',
    'quotes' => 'ad"min',
]);

test('renaming a realm role records the change and renames it on its users', function () {
    $role = Role::factory()->for($this->realm)->create(['name' => 'member', 'description' => null]);
    $user = User::factory()->for($this->realm)->create();
    $user->roles()->attach($role);

    $this->actingAs(globalAdmin())->callAction(UpdateRoleAction::class, [
        'name' => 'staff',
        'description' => 'Renamed',
    ], ['realm' => 'acme', 'role' => $role->id])->assertOk();

    expect($user->refresh()->roleNames())->toBe(['staff']);

    $activity = Activity::forRealm($this->realm)->where('event', RoleAuditEvent::RoleUpdated->eventName())->sole();

    expect($activity->getProperty('changes'))->toBe([
        'name' => ['old' => 'member', 'new' => 'staff'],
        'description' => ['old' => null, 'new' => 'Renamed'],
    ]);
});

test('deleting a realm role detaches every user that holds it', function () {
    $role = Role::factory()->for($this->realm)->create(['name' => 'member']);
    $kept = Role::factory()->for($this->realm)->create(['name' => 'admin']);
    $user = User::factory()->for($this->realm)->create();
    $user->roles()->attach([$role->id, $kept->id]);

    $this->actingAs(globalAdmin())
        ->callAction(DeleteRoleAction::class, [], ['realm' => 'acme', 'role' => $role->id])
        ->assertOk();

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and($user->refresh()->roleNames())->toBe(['admin']);

    $activity = Activity::forRealm($this->realm)->where('event', RoleAuditEvent::RoleDeleted->eventName())->sole();

    expect($activity->getProperty('name'))->toBe('member')
        ->and($activity->getProperty('users-detached'))->toBe(1);
});

test('the roles table lists only the roles of the selected realm with their user counts', function () {
    $member = Role::factory()->for($this->realm)->create(['name' => 'member']);
    Role::factory()->for($this->realm)->create(['name' => 'admin']);
    Role::factory()->for($this->other)->create(['name' => 'elsewhere']);
    User::factory()->for($this->realm)->create()->roles()->attach($member);

    $rows = $this->actingAs(globalAdmin())
        ->loadTable(RolesTable::class, context: ['realm' => 'acme'])
        ->assertOk()
        ->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('name')->all())->toBe(['admin', 'member'])
        ->and(collect(is_array($rows) ? $rows : [])->firstWhere('name', 'member')['users_count'])->toBe(1);
});

test('a realm role is only reachable through its own realm', function () {
    $role = Role::factory()->for($this->realm)->create(['name' => 'member']);
    $foreign = ['realm' => 'globex', 'role' => $role->id];

    $this->actingAs(globalAdmin());
    $this->callDeniedAction(UpdateRoleAction::class, ['name' => 'renamed'], $foreign)->assertForbidden();
    $this->callDeniedAction(DeleteRoleAction::class, [], $foreign)->assertForbidden();

    expect($role->refresh()->name)->toBe('member');
});

test('the roles console is forbidden without a roles scope', function () {
    $role = Role::factory()->for($this->realm)->create(['name' => 'member']);
    $context = ['realm' => 'acme', 'role' => $role->id];

    $this->actingAs(globalAdminWith(Scope::ActivityRead));
    $this->get('/admin/realms/acme/roles')->assertForbidden();
    $this->loadDeniedTable(RolesTable::class, context: ['realm' => 'acme'])->assertForbidden();
    $this->callDeniedAction(CreateRoleAction::class, ['name' => 'other'], ['realm' => 'acme'])->assertForbidden();
    $this->callDeniedAction(UpdateRoleAction::class, ['name' => 'other'], $context)->assertForbidden();
    $this->callDeniedAction(DeleteRoleAction::class, [], $context)->assertForbidden();

    expect($this->realm->roles()->count())->toBe(1);
});

test('assigning realm roles to a user replaces the set and records what changed', function () {
    $member = Role::factory()->for($this->realm)->create(['name' => 'member']);
    $admin = Role::factory()->for($this->realm)->create(['name' => 'admin']);
    $user = User::factory()->for($this->realm)->create();
    $user->roles()->attach($member);

    $this->actingAs(globalAdmin())->callAction(UpdateUserRoles::class, [
        'role_ids' => [$admin->id],
    ], ['realm' => 'acme', 'user' => $user->id])->assertOk();

    expect($user->refresh()->roleNames())->toBe(['admin']);

    $activity = Activity::forRealm($this->realm)->where('event', RoleAuditEvent::AssignmentsUpdated->eventName())->sole();

    expect($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('added'))->toBe(['admin'])
        ->and($activity->getProperty('removed'))->toBe(['member']);
});

test('a role of another realm cannot be assigned to a user', function () {
    $foreign = Role::factory()->for($this->other)->create(['name' => 'elsewhere']);
    $user = User::factory()->for($this->realm)->create();

    $this->actingAs(globalAdmin())->callAction(UpdateUserRoles::class, [
        'role_ids' => [$foreign->id],
    ], ['realm' => 'acme', 'user' => $user->id])->assertInvalid(['role_ids']);

    expect($user->refresh()->roles()->exists())->toBeFalse();
});

test('the role sync action itself refuses a role outside the user\'s realm', function () {
    $foreign = Role::factory()->for($this->other)->create(['name' => 'elsewhere']);
    $user = User::factory()->for($this->realm)->create();

    expect(fn () => app(SyncsUserRoles::class)->handle($user, [$foreign->id]))
        ->toThrow(InvalidArgumentException::class)
        ->and($user->roles()->exists())->toBeFalse();
});
