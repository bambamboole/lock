<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Shared\Auth\Attributes\AsPermission;
use App\Shared\Auth\Concerns\HasPermissionLabel;
use App\Shared\Auth\Contracts\PermissionEnum;

#[AsPermission('navigation.admin', global: true)]
enum AdminPermission: string implements PermissionEnum
{
    use HasPermissionLabel;

    case Access = 'admin:access';

    case ManageUsers = 'admin:users:manage';

    case ManageRealms = 'admin:realms:manage';

    case ManageAdminRoles = 'admin:roles:manage';
}
