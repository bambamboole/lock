<?php
declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;

it('creates the protected global roles when nothing seeded them yet', function () {
    $this->artisan('permission:sync')->assertSuccessful();

    expect(Role::query()->pluck('name')->all())
        ->toEqualCanonicalizing([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT]);
});

it('re-syncs the protected global role grants', function () {
    $this->seed(GlobalRoleSeeder::class);

    $superAdmin = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->sole();
    $superAdmin->syncPermissions([AdminPermission::Access->value]);

    $this->artisan('permission:sync')->assertSuccessful();

    expect($superAdmin->refresh()->permissionValues())
        ->toContain(...array_map(fn (AdminPermission $permission) => $permission->value, AdminPermission::cases()));
});
