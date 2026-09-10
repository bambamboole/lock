<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Auth\Models\User;
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
        $role = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->first()
            ?? throw new RuntimeException('The Super Admin role is not seeded. Run `php artisan permission:sync` first.');

        if ($user->roles()->where('roles.id', $role->id)->exists()) {
            return false;
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        Audit::record(
            AdminAuditEvent::SuperAdminGranted,
            $user,
            properties: ['actor' => $actor, 'added' => [$role->name], 'removed' => []],
        );

        return true;
    }
}
