<?php
declare(strict_types=1);

namespace App\Admin\Support;

use App\Admin\Enums\AdminPermission;

final class GlobalRoles
{
    public const string SUPER_ADMIN = 'Super Admin';

    public const string SUPPORT = 'Support';

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            self::SUPER_ADMIN => array_map(fn (AdminPermission $permission) => $permission->value, AdminPermission::cases()),
            self::SUPPORT => [
                AdminPermission::Access->value,
                AdminPermission::ManageUsers->value,
                AdminPermission::ViewRealms->value,
            ],
        ];
    }
}
