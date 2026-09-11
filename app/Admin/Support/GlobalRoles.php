<?php
declare(strict_types=1);

namespace App\Admin\Support;

use App\Admin\Enums\AdminPermission;

final class GlobalRoles
{
    public const string SUPER_ADMIN = 'Super Admin';

    public const string SUPPORT = 'Support';

    /**
     * @return array<string, list<AdminPermission>>
     */
    public static function grants(): array
    {
        return [
            self::SUPER_ADMIN => AdminPermission::cases(),
            self::SUPPORT => [AdminPermission::Access, AdminPermission::ManageUsers, AdminPermission::ViewRealms],
        ];
    }
}
