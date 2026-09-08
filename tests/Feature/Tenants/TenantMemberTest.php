<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Actions\RemoveMember;
use App\Tenants\Ui\Actions\UpdateMemberRole;
use App\Tenants\Ui\Tables\TenantMembersTable;
use Illuminate\Support\Facades\Notification;
use Lattice\Notifications\LatticeNotification;

use function Tests\Helpers\addMember;
use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\tenantMember;

test('tenant member roles can be updated by owners', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $adminRoleId = $tenant->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertOk();

    expect($member->refresh()->rolesFor($tenant)->pluck('id')->all())->toBe([$adminRoleId]);
});

test('tenant member roles cannot be updated without the manage-members permission', function () {
    [, $tenant] = tenantMember(switch: false);
    $plainMember = User::factory()->create();
    $member = User::factory()->create();
    addMember($tenant, $plainMember);
    addMember($tenant, $member);

    $adminRoleId = $tenant->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($plainMember)
        ->callDeniedAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertForbidden();
});

test('tenant members can be removed by owners', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertOk();

    expect($member->refresh()->belongsToTenant($tenant))->toBeFalse();
});

test('removing a member detaches their tenant roles from the user_role pivot', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Admin);

    expect($member->hasTenantPermission($tenant, TenantPermission::UpdateTenant))->toBeTrue();

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertOk();

    $member->refresh();

    expect($member->rolesFor($tenant))->toBeEmpty()
        ->and($member->hasTenantPermission($tenant, TenantPermission::UpdateTenant))->toBeFalse();
});

test('tenant members cannot be removed without the manage-members permission', function () {
    [, $tenant] = tenantMember(switch: false);
    $plainMember = User::factory()->create();
    $member = User::factory()->create();
    addMember($tenant, $plainMember);
    addMember($tenant, $member);

    $this->actingAs($plainMember)
        ->callDeniedAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertForbidden();
});

test('tenant owner cannot be removed', function () {
    [$owner, $tenant] = tenantMember(switch: false);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->belongsToTenant($tenant))->toBeTrue();
});

test('tenant member role cannot be set to owner', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $ownerRoleId = $tenant->ownerRole()->id;

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $ownerRoleId]], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertSessionHasErrors('role_ids.0');

    expect($member->refresh()->rolesFor($tenant)->pluck('id')->all())->toBe([$tenant->memberRole()->id]);
});

test('the owner cannot change their own role', function () {
    [$owner, $tenant] = tenantMember(switch: false);

    $adminRoleId = $tenant->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['tenant' => $tenant->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->rolesFor($tenant)->pluck('id')->all())->toBe([$tenant->ownerRole()->id]);
});

test('a delegate with role manage and member update permissions cannot reassign the owner', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $delegate = User::factory()->create();

    $customRole = $tenant->roles()->create(['name' => 'Role Manager']);
    $customRole->syncPermissions(['role:manage', 'member:manage']);
    addMember($tenant, $delegate, $customRole);

    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($delegate)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $memberRoleId]], ['tenant' => $tenant->slug, 'member' => $owner->id])
        ->assertForbidden();

    expect($owner->refresh()->rolesFor($tenant)->pluck('id')->all())->toBe([$tenant->ownerRole()->id]);
});

test('removed members receive a persistent notification', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertOk();

    Notification::assertSentTo(
        $member,
        LatticeNotification::class,
        fn (LatticeNotification $notification) => $notification->toArray($member)['title']->key
            === 'tenant:notification.removed.title'
            && (array) $notification->toArray($member)['body']->replacements === ['tenant' => $tenant->name],
    );
});

test('a member who opted out of membership notifications receives no persistent notification when removed', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $member->settings->set('notifications.tenant-membership', false);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertOk();

    Notification::assertNotSentTo($member, LatticeNotification::class);
});

test('removed member current tenant is set to personal tenant', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    $personalTenant = $member->personalTenant();
    assumeNotNull($personalTenant);
    addMember($tenant, $member);

    $member->update(['current_tenant_id' => $tenant->id]);

    $this->actingAs($owner)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id]);

    expect($member->refresh()->current_tenant_id)->toEqual($personalTenant->id);
});

test('the members table filters by name or email through the global search', function () {
    $owner = User::factory()->create(['name' => 'Owner One', 'email' => 'owner@example.com']);
    $member = User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);
    $tenant = Tenant::factory()->create();

    addMember($tenant, $owner, RolePreset::Owner);
    addMember($tenant, $member);

    $this->actingAs($owner)
        ->loadTable(TenantMembersTable::class, ['q' => 'grace'], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('Grace Hopper')
        ->assertDontSee('Owner One');

    $this->actingAs($owner)
        ->loadTable(TenantMembersTable::class, ['q' => 'owner@example.com'], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('Owner One')
        ->assertDontSee('Grace Hopper');
});

test('the members table lists the owner before other members regardless of name', function () {
    $owner = User::factory()->create(['name' => 'Zack Owner', 'email' => 'zack@example.com']);
    $amy = User::factory()->create(['name' => 'Amy Member', 'email' => 'amy@example.com']);
    $bob = User::factory()->create(['name' => 'Bob Member', 'email' => 'bob@example.com']);
    $tenant = Tenant::factory()->create();

    addMember($tenant, $owner, RolePreset::Owner);
    addMember($tenant, $amy);
    addMember($tenant, $bob);

    $response = $this->actingAs($owner)
        ->loadTable(TenantMembersTable::class, [], ['tenant' => $tenant->slug])
        ->assertOk();

    expect($response->collect('data')->pluck('id')->all())->toBe([$owner->id, $amy->id, $bob->id]);
});

test('a role from another tenant cannot be assigned', function () {
    [$owner, $tenant] = tenantMember();
    $member = User::factory()->create();
    addMember($tenant, $member);

    [, $otherTenant] = tenantMember();
    $foreignRoleId = $otherTenant->roles()->where('preset', RolePreset::Admin)->firstOrFail()->id;

    $this->actingAs($owner)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [$foreignRoleId]], ['tenant' => $tenant->slug, 'member' => $member->id])
        ->assertSessionHasErrors('role_ids.0');

    expect($member->refresh()->rolesFor($tenant)->pluck('id')->all())
        ->toBe([$tenant->memberRole()->id]);
});

test('an owner can assign multiple roles to a member', function () {
    $owner = User::factory()->create();
    $tenant = $owner->currentTenant()->sole();

    $member = User::factory()->create();
    addMember($tenant, $member);

    $roleManager = Role::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Role manager']);
    $roleManager->syncPermissions([TenantPermission::ManageRoles->value]);

    $this->actingAs($owner)->callAction(UpdateMemberRole::class, [
        'role_ids' => [$tenant->memberRole()->id, $roleManager->id],
    ], ['tenant' => $tenant->slug, 'member' => $member->id]);

    expect($member->refresh()->rolesFor($tenant)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$tenant->memberRole()->id, $roleManager->id])->sort()->values()->all());
});

test('assigning roles to a member sets them to exactly the submitted set with no phantom extras', function () {
    $owner = User::factory()->create();
    $tenant = $owner->currentTenant()->sole();

    $member = User::factory()->create();
    addMember($tenant, $member);

    $roleManager = Role::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Role manager']);
    $roleManager->syncPermissions([TenantPermission::ManageRoles->value]);

    $this->actingAs($owner)->callAction(UpdateMemberRole::class, [
        'role_ids' => [$roleManager->id],
    ], ['tenant' => $tenant->slug, 'member' => $member->id]);

    expect($member->refresh()->rolesFor($tenant)->pluck('id')->all())->toBe([$roleManager->id]);
});
