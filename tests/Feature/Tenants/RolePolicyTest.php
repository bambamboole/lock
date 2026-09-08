<?php
declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantPermission;

use function Tests\Helpers\tenantMember;

it('lets owners manage roles implicitly and admins/members not', function () {
    [$owner, $tenantO] = tenantMember(RolePreset::Owner, switch: false);
    [$admin, $tenantA] = tenantMember(RolePreset::Admin, switch: false);
    [$member, $tenantM] = tenantMember(RolePreset::Member, switch: false);

    expect($owner->can(TenantPermission::ManageRoles, $tenantO))->toBeTrue()
        ->and($admin->can(TenantPermission::ManageRoles, $tenantA))->toBeFalse()
        ->and($member->can(TenantPermission::ManageRoles, $tenantM))->toBeFalse();
});
