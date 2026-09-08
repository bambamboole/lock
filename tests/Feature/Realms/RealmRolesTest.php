<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\RealmInvitation;
use App\Realms\Models\Role;
use App\Realms\Ui\Actions\DeleteRole;
use App\Realms\Ui\Forms\RoleForm;
use App\Realms\Ui\Tables\RealmRolesTable;
use App\Shared\Audit\Models\Activity;

use function Tests\Helpers\addMember;
use function Tests\Helpers\realmMember;

test('a role can be created', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['audit:view']], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    $role = Role::query()->where('realm_id', $realm->id)->where('name', 'Support')->firstOrFail();

    expect($role->permissionValues())->toBe(['audit:view']);

    $activity = Activity::query()->where('event', 'role.created')->where('realm_id', $realm->id)->latest('id')->firstOrFail();
    expect($activity->subject_id)->toBe($role->id);
});

test('a role cannot be created with a duplicate name', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $realm->roles()->create(['name' => 'Support']);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => []], ['realm' => $realm->slug])
        ->assertSessionHasErrors('name');
});

test('a role cannot be created with an unknown permission', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['bogus:scope']], ['realm' => $realm->slug])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('realm_id', $realm->id)->where('name', 'Support')->exists())->toBeFalse();
});

test('a role cannot be created with an admin permission', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => [AdminPermission::Access->value]], ['realm' => $realm->slug])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('realm_id', $realm->id)->where('name', 'Support')->exists())->toBeFalse();
});

test('a role can be edited', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);
    $role->syncPermissions(['audit:view']);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support Team', 'permissions' => ['audit:view', 'member:manage']], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertRedirect(route('realm.settings.edit'));

    $role->refresh();

    expect($role->name)->toBe('Support Team')
        ->and($role->permissionValues())->toEqualCanonicalizing(['audit:view', 'member:manage']);

    $activity = Activity::query()->where('event', 'role.updated')->where('realm_id', $realm->id)->latest('id')->firstOrFail();
    expect($activity->subject_id)->toBe($role->id);
});

test('an unused custom role can be deleted', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertOk();

    expect(Role::query()->find($role->id))->toBeNull()
        ->and(Activity::query()->where('event', 'role.deleted')->where('realm_id', $realm->id)->count())->toBe(1);
});

test('a role in use cannot be deleted', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);
    addMember($realm, User::factory()->for($realm)->create(), $role);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertStatus(422);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('a role referenced only by an accepted invitation can be deleted', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);
    $invitation = RealmInvitation::factory()->accepted()->create([
        'realm_id' => $realm->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertOk();

    expect(Role::query()->find($role->id))->toBeNull();

    $invitation->refresh();
    expect(RealmInvitation::query()->find($invitation->id))->not->toBeNull()
        ->and($invitation->role_id)->toBeNull();
});

test('a role referenced by a pending invitation cannot be deleted', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);
    RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertStatus(422);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('members cannot delete roles', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $role = $realm->roles()->create(['name' => 'Support']);
    [$member] = realmMember(RolePreset::Member, realm: $realm);

    $this->actingAs($member)
        ->callDeniedAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $role->id])
        ->assertForbidden();

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('the roles table exposes row actions on custom roles but none on the owner preset', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $custom = $realm->roles()->create(['name' => 'Support']);

    $response = $this->actingAs($owner)
        ->loadTable(RealmRolesTable::class, [], ['realm' => $realm->slug])
        ->assertOk();

    $rows = $response->collect('data');
    $ownerRow = $rows->firstWhere('id', $realm->ownerRole()->id);
    $customRow = $rows->firstWhere('id', $custom->id);

    expect($ownerRow)->not->toBeNull()
        ->and($ownerRow['actions'] ?? [])->toBe([])
        ->and($customRow)->not->toBeNull()
        ->and($customRow['actions'] ?? [])->not->toBeEmpty();
});

test('the owner preset role cannot be deleted', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $ownerRoleId = $realm->ownerRole()->id;

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['realm' => $realm->slug, 'role' => $ownerRoleId])
        ->assertForbidden();
});

test('a custom role granted the manage-roles permission can itself manage roles', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $customRole = $realm->roles()->create(['name' => 'Role Manager']);
    $customRole->syncPermissions(['role:manage']);

    $delegate = User::factory()->for($realm)->create();
    addMember($realm, $delegate, $customRole);

    $this->actingAs($delegate)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['audit:view']], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    expect(Role::query()->where('realm_id', $realm->id)->where('name', 'Support')->exists())->toBeTrue();
});

test('the owner preset role cannot be edited', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $ownerRoleId = $realm->ownerRole()->id;

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Hacked Owner', 'permissions' => []], ['realm' => $realm->slug, 'role' => $ownerRoleId])
        ->assertForbidden();
});

test('members cannot load the roles table', function () {
    [$owner, $realm] = realmMember();

    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $this->actingAs($member)
        ->loadDeniedTable(RealmRolesTable::class, [], ['realm' => $realm->slug])
        ->assertForbidden();
});

test('authorization precedes validation: a member submitting a duplicate role name is denied', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);
    $realm->roles()->create(['name' => 'Support']);

    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $this->actingAs($member)
        ->submitDeniedForm(RoleForm::class, ['name' => 'Support', 'permissions' => []], ['realm' => $realm->slug])
        ->assertForbidden();
});
