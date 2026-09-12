<?php
declare(strict_types=1);

namespace App\Shared\Api\Enums;

/**
 * The scopes the management API declares. They are OAuth scopes, not
 * permissions: a token carries them, and `CheckScopes` is the only thing that
 * reads them. The descriptions are stored on the scope rows, so they stay in
 * English like every other scope value on the wire.
 */
enum ApiScope: string
{
    case RealmsRead = 'realms:read';

    case RealmsWrite = 'realms:write';

    public function description(): string
    {
        return match ($this) {
            self::RealmsRead => 'Read realms and their domain state.',
            self::RealmsWrite => 'Create, rename, re-point and delete realms.',
        };
    }

    /** @return array<string, string> */
    public static function catalog(): array
    {
        $catalog = [];

        foreach (self::cases() as $scope) {
            $catalog[$scope->value] = $scope->description();
        }

        return $catalog;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
