<?php
declare(strict_types=1);

namespace App\Tenants\Enums;

enum RolePreset: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return array<int, TenantPermission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Owner => [],
            self::Admin => [
                TenantPermission::UpdateTenant,
                TenantPermission::ManageMembers,
                TenantPermission::ViewAudit,
            ],
            self::Member => [],
        };
    }

    public function label(): string
    {
        return __('tenant.roles.'.$this->value);
    }
}
