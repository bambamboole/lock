<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Audit;
use RuntimeException;

final class GrantSuperAdmin
{
    /**
     * @param  string  $actor  what asked for the grant, recorded on the audit entry
     * @return bool whether the role was newly granted
     */
    public function handle(User $user, string $actor): bool
    {
        $role = $user->realm->realmRoles()->where('name', ManagementRoles::SUPER_ADMIN)->first()
            ?? throw new RuntimeException('The Super Admin role is not seeded. Run `php artisan app:bootstrap` first.');

        if ($user->realmRoles()->where('realm_roles.id', $role->id)->exists()) {
            return false;
        }

        $user->realmRoles()->syncWithoutDetaching([$role->id]);

        Audit::record(
            AdminAuditEvent::SuperAdminGranted,
            $user,
            properties: ['actor' => $actor, 'added' => [$role->name], 'removed' => []],
        );

        return true;
    }
}
