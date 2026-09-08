<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;

test('a member gets the union of permissions from all their tenant roles', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant()->sole();

    $roleManager = Role::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Role manager',
    ]);
    $roleManager->syncPermissions([TenantPermission::ManageRoles->value]);

    $auditor = Role::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Auditor',
    ]);
    $auditor->syncPermissions([TenantPermission::ViewAudit->value]);

    $member = User::factory()->create();
    $tenant->members()->attach($member);
    $member->roles()->attach([$roleManager->id, $auditor->id]);

    expect($member->hasTenantPermission($tenant, TenantPermission::ManageRoles))->toBeTrue()
        ->and($member->hasTenantPermission($tenant, TenantPermission::ViewAudit))->toBeTrue()
        ->and($member->hasTenantPermission($tenant, TenantPermission::DeleteTenant))->toBeFalse();
});
