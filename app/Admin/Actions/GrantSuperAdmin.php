<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Auth\Models\User;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Enums\UserAuditEvent;
use RuntimeException;

final class GrantSuperAdmin
{
    /**
     * @param  string  $actor  what asked for the grant, recorded on the audit entry
     * @return bool whether the role was newly granted
     */
    public function handle(User $user, string $actor): bool
    {
        $role = $user->realm->roles()->where('name', ManagementRoles::SUPER_ADMIN)->first()
            ?? throw new RuntimeException('The Super Admin role is not seeded. Run `php artisan app:bootstrap` first.');

        if ($user->roles()->where('roles.id', $role->id)->exists()) {
            return false;
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        Audit::record(
            UserAuditEvent::SuperAdminGranted,
            $user,
            properties: ['actor' => $actor, 'added' => [$role->name], 'removed' => []],
        );

        return true;
    }
}
