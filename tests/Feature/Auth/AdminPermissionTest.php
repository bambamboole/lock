<?php

declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\Permission;
use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\addMember;

test('a user with a global role granting admin:access is an admin', function () {
    $user = User::factory()->create();
    $role = Role::factory()->global()->create(['name' => 'Super Admin']);
    $role->syncPermissions([
        AdminPermission::Access->value,
        AdminPermission::ManageUsers->value,
    ]);
    $user->roles()->attach($role);

    expect($user->can(AdminPermission::Access))->toBeTrue()
        ->and($user->can(AdminPermission::ManageUsers))->toBeTrue()
        ->and($user->can(AdminPermission::ManageTenants))->toBeFalse();
});

test('a tenant role cannot be given a global permission', function () {
    $tenant = Tenant::factory()->create();
    $tenantRole = Role::factory()->for($tenant)->create(['name' => 'Support']);

    $tenantRole->syncPermissions([AdminPermission::Access->value]);
})->throws(InvalidArgumentException::class);

test('a global permission smuggled onto a tenant role grants nothing', function () {
    $tenant = Tenant::factory()->create();
    $tenantRole = Role::factory()->for($tenant)->create(['name' => 'Support']);
    $tenantRole->permissions()->attach(
        Permission::query()->create(['key' => AdminPermission::Access->value])->id,
    );

    $user = User::factory()->create();
    addMember($tenant, $user, $tenantRole);

    expect($user->can(AdminPermission::Access))->toBeFalse()
        ->and($user->can(AdminPermission::Access))->toBeFalse()
        ->and($user->can(AdminPermission::Access, $tenant))->toBeFalse()
        ->and($user->hasTenantPermission($tenant, AdminPermission::Access))->toBeFalse();
});

test('a tenant owner reaches every tenant permission but no global one', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();
    addMember($tenant, $owner, $tenant->ownerRole());

    expect($owner->can(TenantPermission::ManageRoles, $tenant))->toBeTrue()
        ->and($owner->can(AdminPermission::ManageUsers, $tenant))->toBeFalse()
        ->and($owner->can(AdminPermission::Access))->toBeFalse();
});

test('a granted :manage permission satisfies its :view counterpart', function () {
    $tenant = Tenant::factory()->create();
    $role = Role::factory()->for($tenant)->create(['name' => 'Role admin']);
    $role->syncPermissions([TenantPermission::ManageRoles->value]);

    $user = User::factory()->create();
    addMember($tenant, $user, $role);

    expect($user->can(TenantPermission::ManageRoles, $tenant))->toBeTrue()
        ->and($user->can(TenantPermission::ViewAudit, $tenant))->toBeFalse();
});
