<?php
declare(strict_types=1);

namespace App\Realms\Enums;

enum RolePreset: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return array<int, RealmPermission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Owner => [],
            self::Admin => [
                RealmPermission::UpdateRealm,
                RealmPermission::ManageMembers,
                RealmPermission::ViewAudit,
            ],
            self::Member => [],
        };
    }

    public function label(): string
    {
        return __('realm.roles.'.$this->value);
    }
}
