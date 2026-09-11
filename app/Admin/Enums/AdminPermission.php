<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Shared\Auth\Contracts\PermissionEnum;

enum AdminPermission: string implements PermissionEnum
{
    case Access = 'admin:access';

    case ManageUsers = 'admin:users:manage';

    case ViewRealms = 'admin:realms:view';

    case ManageRealms = 'admin:realms:manage';

    case ViewResources = 'admin:resources:view';

    case ManageResources = 'admin:resources:manage';

    case ManageAdminRoles = 'admin:roles:manage';
}
