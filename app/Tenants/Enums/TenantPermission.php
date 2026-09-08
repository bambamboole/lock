<?php
declare(strict_types=1);

namespace App\Tenants\Enums;

use App\Auth\Attributes\AsPermission;
use App\Auth\Attributes\AsScope;
use App\Auth\Concerns\HasPermissionLabel;
use App\Auth\Contracts\PermissionEnum;

#[AsPermission('navigation.tenant-settings')]
enum TenantPermission: string implements PermissionEnum
{
    use HasPermissionLabel;

    /*
     * Only cases backing the members API surface carry #[AsScope] — a
     * published scope without an endpoint is an empty promise in the token
     * picker. Tag a case together with the endpoint that checks it.
     */
    case UpdateTenant = 'tenant:update';

    case DeleteTenant = 'tenant:delete';

    #[AsScope]
    case ViewMembers = 'member:view';

    #[AsScope]
    case ManageMembers = 'member:manage';

    case ViewAudit = 'audit:view';

    case ManageBilling = 'billing:manage';

    case ManageRoles = 'role:manage';
}
