<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Auth\Attributes\AsPermission;
use App\Auth\Concerns\HasPermissionLabel;
use App\Auth\Contracts\PermissionEnum;

#[AsPermission('navigation.admin', global: true)]
enum AdminPermission: string implements PermissionEnum
{
    use HasPermissionLabel;

    case Access = 'admin:access';

    case ManageUsers = 'admin:users:manage';

    case ManageTenants = 'admin:tenants:manage';

    case ManageAdminRoles = 'admin:roles:manage';
}
