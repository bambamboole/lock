<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Role;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Actions\DeleteRole;
use App\Tenants\Ui\Forms\RoleForm;
use App\Tenants\Ui\Tables\TenantRolesTable;

use function Tests\Helpers\addMember;
use function Tests\Helpers\tenantMember;

test('a role can be created', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['audit:view']], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'roles']));

    $role = Role::query()->where('tenant_id', $tenant->id)->where('name', 'Support')->firstOrFail();

    expect($role->permissionValues())->toBe(['audit:view']);

    $activity = Activity::query()->where('event', 'role.created')->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
    expect($activity->subject_id)->toBe($role->id);
});

test('a role cannot be created with a duplicate name', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $tenant->roles()->create(['name' => 'Support']);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => []], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('name');
});

test('a role cannot be created with an unknown permission', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['bogus:scope']], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('tenant_id', $tenant->id)->where('name', 'Support')->exists())->toBeFalse();
});

test('a role cannot be created with an admin permission', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => [AdminPermission::Access->value]], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('tenant_id', $tenant->id)->where('name', 'Support')->exists())->toBeFalse();
});

test('a role can be edited', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);
    $role->syncPermissions(['audit:view']);

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Support Team', 'permissions' => ['audit:view', 'member:manage']], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'roles']));

    $role->refresh();

    expect($role->name)->toBe('Support Team')
        ->and($role->permissionValues())->toEqualCanonicalizing(['audit:view', 'member:manage']);

    $activity = Activity::query()->where('event', 'role.updated')->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
    expect($activity->subject_id)->toBe($role->id);
});

test('an unused custom role can be deleted', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertOk();

    expect(Role::query()->find($role->id))->toBeNull()
        ->and(Activity::query()->where('event', 'role.deleted')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('a role in use cannot be deleted', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);
    addMember($tenant, User::factory()->create(), $role);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertStatus(422);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('a role referenced only by an accepted invitation can be deleted', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);
    $invitation = TenantInvitation::factory()->accepted()->create([
        'tenant_id' => $tenant->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertOk();

    expect(Role::query()->find($role->id))->toBeNull();

    $invitation->refresh();
    expect(TenantInvitation::query()->find($invitation->id))->not->toBeNull()
        ->and($invitation->role_id)->toBeNull();
});

test('a role referenced by a pending invitation cannot be deleted', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);
    TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertStatus(422);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('members cannot delete roles', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $role = $tenant->roles()->create(['name' => 'Support']);
    [$member] = tenantMember(RolePreset::Member, tenant: $tenant);

    $this->actingAs($member)
        ->callDeniedAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $role->id])
        ->assertForbidden();

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('the roles table exposes row actions on custom roles but none on the owner preset', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $custom = $tenant->roles()->create(['name' => 'Support']);

    $response = $this->actingAs($owner)
        ->loadTable(TenantRolesTable::class, [], ['tenant' => $tenant->slug])
        ->assertOk();

    $rows = $response->collect('data');
    $ownerRow = $rows->firstWhere('id', $tenant->ownerRole()->id);
    $customRow = $rows->firstWhere('id', $custom->id);

    expect($ownerRow)->not->toBeNull()
        ->and($ownerRow['actions'] ?? [])->toBe([])
        ->and($customRow)->not->toBeNull()
        ->and($customRow['actions'] ?? [])->not->toBeEmpty();
});

test('the owner preset role cannot be deleted', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $ownerRoleId = $tenant->ownerRole()->id;

    $this->actingAs($owner)
        ->callAction(DeleteRole::class, [], ['tenant' => $tenant->slug, 'role' => $ownerRoleId])
        ->assertForbidden();
});

test('a custom role granted the manage-roles permission can itself manage roles', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $customRole = $tenant->roles()->create(['name' => 'Role Manager']);
    $customRole->syncPermissions(['role:manage']);

    $delegate = User::factory()->create();
    addMember($tenant, $delegate, $customRole);

    $this->actingAs($delegate)
        ->submitForm(RoleForm::class, ['name' => 'Support', 'permissions' => ['audit:view']], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'roles']));

    expect(Role::query()->where('tenant_id', $tenant->id)->where('name', 'Support')->exists())->toBeTrue();
});

test('the owner preset role cannot be edited', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $ownerRoleId = $tenant->ownerRole()->id;

    $this->actingAs($owner)
        ->submitForm(RoleForm::class, ['name' => 'Hacked Owner', 'permissions' => []], ['tenant' => $tenant->slug, 'role' => $ownerRoleId])
        ->assertForbidden();
});

test('members cannot load the roles table', function () {
    [$owner, $tenant] = tenantMember();

    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($member)
        ->loadDeniedTable(TenantRolesTable::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('authorization precedes validation: a member submitting a duplicate role name is denied', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $tenant->roles()->create(['name' => 'Support']);

    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($member)
        ->submitDeniedForm(RoleForm::class, ['name' => 'Support', 'permissions' => []], ['tenant' => $tenant->slug])
        ->assertForbidden();
});
