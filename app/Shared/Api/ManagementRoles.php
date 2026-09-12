<?php
declare(strict_types=1);

namespace App\Shared\Api;

use App\Shared\Api\Enums\ApiScope;

/**
 * The roles Lock ships with: realm roles of the master realm whose scopes are
 * the management API's. They are ordinary roles otherwise — the console lists
 * and assigns them like any other — but `app:bootstrap` owns their scope sets,
 * so nothing else may rename or delete them.
 *
 * There is no implication between a read and a write scope, so a role that may
 * write lists both.
 */
final class ManagementRoles
{
    public const string SUPER_ADMIN = 'Super Admin';

    public const string SUPPORT = 'Support';

    /**
     * @return array<string, list<ApiScope>>
     */
    public static function grants(): array
    {
        return [
            self::SUPER_ADMIN => ApiScope::cases(),
            self::SUPPORT => [
                ApiScope::RealmsRead,
                ApiScope::UsersRead,
                ApiScope::UsersWrite,
                ApiScope::RolesRead,
                ApiScope::ActivityRead,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::grants());
    }
}
