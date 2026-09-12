<?php
declare(strict_types=1);

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Shared\Admin\Enums\AdminPermission;

it('creates the protected global roles when nothing seeded them yet', function () {
    Role::query()->delete();

    $this->artisan('permission:sync')->assertSuccessful();

    expect(Role::query()->pluck('name')->all())
        ->toEqualCanonicalizing([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT]);
});

it('re-syncs the protected global role grants', function () {
    $superAdmin = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->sole();
    $superAdmin->syncPermissions([AdminPermission::Access]);

    $this->artisan('permission:sync')->assertSuccessful();

    expect($superAdmin->refresh()->permissionValues())->toEqualCanonicalizing(array_column(AdminPermission::cases(), 'value'))
        ->and(Role::query()->count())->toBe(2);
});
