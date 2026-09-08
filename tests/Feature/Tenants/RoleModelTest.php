<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\Permission;
use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;

it('seeds the three preset roles when a tenant is created', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->roles()->pluck('preset')->map(fn (RolePreset $preset) => $preset->value)->all())
        ->toEqualCanonicalizing(['owner', 'admin', 'member']);
});

it('seeds the admin matrix and an empty member set', function () {
    $tenant = Tenant::factory()->create();

    $admin = $tenant->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $member = $tenant->memberRole();
    $owner = $tenant->ownerRole();

    expect($admin->permissionValues())->toEqualCanonicalizing([
        'tenant:update', 'member:manage', 'audit:view', 'billing:manage',
    ])
        ->and($member->permissionValues())->toBeEmpty()
        ->and($owner->permissionValues())->toBeEmpty()
        ->and($owner->isOwner())->toBeTrue();
});

it('creates permission rows and attaches them when syncing permissions', function () {
    $tenant = Tenant::factory()->create();
    $role = Role::factory()->for($tenant)->create(['name' => 'Support']);

    $role->syncPermissions(['audit:view', 'member:manage']);

    expect($role->permissionValues())->toEqualCanonicalizing(['audit:view', 'member:manage'])
        ->and(Permission::query()->whereIn('key', ['audit:view', 'member:manage'])->count())->toBe(2);
});

it('shares a permission row across roles instead of duplicating it', function () {
    $tenant = Tenant::factory()->create();
    $admin = $tenant->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $support = Role::factory()->for($tenant)->create(['name' => 'Support']);

    $support->syncPermissions(['audit:view']);

    $permission = Permission::query()->where('key', 'audit:view')->firstOrFail();

    expect(Permission::query()->where('key', 'audit:view')->count())->toBe(1)
        ->and($admin->permissions()->whereKey($permission->id)->exists())->toBeTrue()
        ->and($support->permissions()->whereKey($permission->id)->exists())->toBeTrue();
});

it('rejects unknown permission strings when syncing', function (bool $global) {
    $role = $global
        ? Role::factory()->global()->create(['name' => 'Platform Admin'])
        : Role::factory()->for(Tenant::factory()->create())->create(['name' => 'Support']);

    $role->syncPermissions(['not-a-permission']);
})->with([true, false])->throws(InvalidArgumentException::class);

it('rejects admin permissions when syncing a tenant role', function () {
    $tenant = Tenant::factory()->create();
    $role = Role::factory()->for($tenant)->create(['name' => 'Support']);

    $role->syncPermissions([AdminPermission::Access->value]);
})->throws(InvalidArgumentException::class);

it('accepts admin permissions when syncing a global role', function () {
    $role = Role::factory()->global()->create(['name' => 'Platform Admin']);

    $role->syncPermissions([AdminPermission::Access->value]);

    expect($role->permissionValues())->toBe([AdminPermission::Access->value]);
});

it('scopes tenant roles to the tenant and excludes global rows', function () {
    $tenant = Tenant::factory()->create();
    Role::factory()->global()->create(['name' => 'Platform Admin']);

    expect($tenant->roles()->count())->toBe(3)
        ->and($tenant->assignableRoles()->pluck('preset')->filter()->map(fn (RolePreset $preset) => $preset->value)->all())->not->toContain('owner');
});

it('translates the preset label while the preset row keeps its seeded default name', function () {
    $tenant = Tenant::factory()->create();

    $admin = $tenant->roles()->where('preset', RolePreset::Admin)->firstOrFail();

    expect($admin->displayName())->toBe(RolePreset::Admin->label());
});

it('shows the raw name once a preset role has been renamed', function () {
    $tenant = Tenant::factory()->create();

    $admin = $tenant->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $admin->update(['name' => 'Team Lead']);

    expect($admin->displayName())->toBe('Team Lead');
});

it('scopes globalRoles to roles without a tenant', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant()->sole();
    $global = Role::factory()->global()->create(['name' => 'Super Admin']);
    $user->roles()->attach($global);

    expect($user->globalRoles()->pluck('id')->all())->toBe([$global->id])
        ->and($user->rolesFor($tenant)->pluck('tenant_id')->unique()->all())->toBe([$tenant->id]);
});
