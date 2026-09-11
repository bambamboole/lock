<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Admin\Models\Permission;
use App\Admin\Models\Role;

it('shares a permission row across roles instead of duplicating it', function () {
    $support = Role::factory()->create(['name' => 'Support']);
    $auditor = Role::factory()->create(['name' => 'Auditor']);

    $support->syncPermissions([AdminPermission::Access]);
    $auditor->syncPermissions([AdminPermission::Access]);

    $permission = Permission::query()->where('key', AdminPermission::Access->value)->sole();

    expect($support->permissions()->whereKey($permission->id)->exists())->toBeTrue()
        ->and($auditor->permissions()->whereKey($permission->id)->exists())->toBeTrue();
});

it('prunes permission rows the enum no longer declares', function () {
    Permission::query()->create(['key' => 'ghost:permission']);

    Role::factory()->create()->syncPermissions([AdminPermission::ViewRealms]);

    expect(Permission::query()->pluck('key')->all())->toBe([AdminPermission::ViewRealms->value]);
});
