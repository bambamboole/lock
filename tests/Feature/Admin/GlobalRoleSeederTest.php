<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Tenants\Models\Role;

test('the seeder creates protected global roles idempotently', function () {

    (new GlobalRoleSeeder)->run();
    (new GlobalRoleSeeder)->run();

    $superAdmin = Role::query()->whereNull('tenant_id')->where('name', GlobalRoleSeeder::SUPER_ADMIN)->sole();

    expect(Role::query()->whereNull('tenant_id')->count())->toBe(2);

    $expected = array_map(fn (AdminPermission $p) => $p->value, AdminPermission::cases());
    expect($superAdmin->permissionValues())->toEqualCanonicalizing($expected);
});
