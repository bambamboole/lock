<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Realms\Ui\Actions\RemoveMember;
use App\Realms\Ui\Actions\UpdateMemberRole;
use App\Realms\Ui\Tables\RealmMembersTable;

use function Tests\Helpers\addMember;
use function Tests\Helpers\realmMember;

test('realm member roles can be updated by owners', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $adminRoleId = $realm->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertOk();

    expect($member->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$adminRoleId]);
});

test('realm member roles cannot be updated without the manage-members permission', function () {
    [, $realm] = realmMember();
    $plainMember = User::factory()->for($realm)->create();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $plainMember);
    addMember($realm, $member);

    $adminRoleId = $realm->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($plainMember)
        ->callDeniedAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertForbidden();
});

test('realm members can be removed by owners', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

test('removing a member detaches their realm roles from the user_role pivot', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member, RolePreset::Admin);

    expect($member->hasRealmPermission($realm, RealmPermission::UpdateRealm))->toBeTrue();

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertDatabaseMissing('user_role', ['user_id' => $member->id]);
});

test('realm members cannot be removed without the manage-members permission', function () {
    [, $realm] = realmMember();
    $plainMember = User::factory()->for($realm)->create();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $plainMember);
    addMember($realm, $member);

    $this->actingAs($plainMember)
        ->callDeniedAction(RemoveMember::class, [], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertForbidden();
});

test('realm owner cannot be removed', function () {
    [$owner, $realm] = realmMember();

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['realm' => $realm->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->belongsToRealm($realm))->toBeTrue();
});

test('realm member role cannot be set to owner', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $ownerRoleId = $realm->ownerRole()->id;

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $ownerRoleId]], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertSessionHasErrors('role_ids.0');

    expect($member->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$realm->memberRole()->id]);
});

test('the owner cannot change their own role', function () {
    [$owner, $realm] = realmMember();

    $adminRoleId = $realm->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['realm' => $realm->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$realm->ownerRole()->id]);
});

test('a delegate with role manage and member update permissions cannot reassign the owner', function () {
    [$owner, $realm] = realmMember();
    $delegate = User::factory()->for($realm)->create();

    $customRole = $realm->roles()->create(['name' => 'Role Manager']);
    $customRole->syncPermissions(['role:manage', 'member:manage']);
    addMember($realm, $delegate, $customRole);

    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($delegate)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $memberRoleId]], ['realm' => $realm->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$realm->ownerRole()->id]);
});

test('the members table filters by name or email through the global search', function () {
    $realm = Realm::factory()->create();
    $owner = User::factory()->for($realm)->create(['name' => 'Owner One', 'email' => 'owner@example.com']);
    $member = User::factory()->for($realm)->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    addMember($realm, $owner, RolePreset::Owner);
    addMember($realm, $member);

    $this->actingAs($owner)
        ->loadTable(RealmMembersTable::class, ['q' => 'grace'], ['realm' => $realm->slug])
        ->assertOk()
        ->assertSee('Grace Hopper')
        ->assertDontSee('Owner One');

    $this->actingAs($owner)
        ->loadTable(RealmMembersTable::class, ['q' => 'owner@example.com'], ['realm' => $realm->slug])
        ->assertOk()
        ->assertSee('Owner One')
        ->assertDontSee('Grace Hopper');
});

test('the members table lists the owner before other members regardless of name', function () {
    $realm = Realm::factory()->create();
    $owner = User::factory()->for($realm)->create(['name' => 'Zack Owner', 'email' => 'zack@example.com']);
    $amy = User::factory()->for($realm)->create(['name' => 'Amy Member', 'email' => 'amy@example.com']);
    $bob = User::factory()->for($realm)->create(['name' => 'Bob Member', 'email' => 'bob@example.com']);

    addMember($realm, $owner, RolePreset::Owner);
    addMember($realm, $amy);
    addMember($realm, $bob);

    $response = $this->actingAs($owner)
        ->loadTable(RealmMembersTable::class, [], ['realm' => $realm->slug])
        ->assertOk();

    expect($response->collect('data')->pluck('id')->all())->toBe([$owner->id, $amy->id, $bob->id]);
});

test('a role from another realm cannot be assigned', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    [, $otherRealm] = realmMember();
    $foreignRoleId = $otherRealm->roles()->where('preset', RolePreset::Admin)->firstOrFail()->id;

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [$foreignRoleId]], ['realm' => $realm->slug, 'member' => $member->id])
        ->assertSessionHasErrors('role_ids.0');

    expect($member->refresh()->rolesFor($realm)->pluck('id')->all())
        ->toBe([$realm->memberRole()->id]);
});

test('an owner can assign multiple roles to a member', function () {
    [$owner, $realm] = realmMember();

    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $roleManager = Role::factory()->create(['realm_id' => $realm->id, 'name' => 'Role manager']);
    $roleManager->syncPermissions([RealmPermission::ManageRoles->value]);

    $this->actingAs($owner)->callAction(UpdateMemberRole::class, [
        'role_ids' => [$realm->memberRole()->id, $roleManager->id],
    ], ['realm' => $realm->slug, 'member' => $member->id]);

    expect($member->refresh()->rolesFor($realm)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$realm->memberRole()->id, $roleManager->id])->sort()->values()->all());
});

test('assigning roles to a member sets them to exactly the submitted set with no phantom extras', function () {
    [$owner, $realm] = realmMember();

    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $roleManager = Role::factory()->create(['realm_id' => $realm->id, 'name' => 'Role manager']);
    $roleManager->syncPermissions([RealmPermission::ManageRoles->value]);

    $this->actingAs($owner)->callAction(UpdateMemberRole::class, [
        'role_ids' => [$roleManager->id],
    ], ['realm' => $realm->slug, 'member' => $member->id]);

    expect($member->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$roleManager->id]);
});
