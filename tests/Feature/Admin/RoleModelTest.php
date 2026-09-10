<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Admin\Models\Permission;
use App\Admin\Models\Role;

it('creates permission rows and attaches them when syncing permissions', function () {
    $role = Role::factory()->create(['name' => 'Support']);

    $role->syncPermissions([AdminPermission::Access->value, AdminPermission::ManageUsers->value]);

    expect($role->permissionValues())->toEqualCanonicalizing([AdminPermission::Access->value, AdminPermission::ManageUsers->value])
        ->and(Permission::query()->whereIn('key', [AdminPermission::Access->value, AdminPermission::ManageUsers->value])->count())->toBe(2);
});

it('shares a permission row across roles instead of duplicating it', function () {
    $support = Role::factory()->create(['name' => 'Support']);
    $auditor = Role::factory()->create(['name' => 'Auditor']);

    $support->syncPermissions([AdminPermission::Access->value]);
    $auditor->syncPermissions([AdminPermission::Access->value]);

    $permission = Permission::query()->where('key', AdminPermission::Access->value)->firstOrFail();

    expect(Permission::query()->where('key', AdminPermission::Access->value)->count())->toBe(1)
        ->and($support->permissions()->whereKey($permission->id)->exists())->toBeTrue()
        ->and($auditor->permissions()->whereKey($permission->id)->exists())->toBeTrue();
});

it('rejects unknown permission strings when syncing', function () {
    Role::factory()->create(['name' => 'Platform Admin'])->syncPermissions(['not-a-permission']);
})->throws(InvalidArgumentException::class);
