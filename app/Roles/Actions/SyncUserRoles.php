<?php
declare(strict_types=1);

namespace App\Roles\Actions;

use App\Auth\Models\User;
use App\Roles\Models\Role;
use App\Shared\Audit\Audit;
use App\Shared\Roles\Contracts\SyncsUserRoles;
use App\Shared\Roles\Enums\RoleAuditEvent;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SyncUserRoles implements SyncsUserRoles
{
    public function handle(User $user, array $roleIds): void
    {
        $roleIds = array_values(array_unique($roleIds));
        $after = $user->realm->roles()->findMany($roleIds);

        if ($after->count() !== count($roleIds)) {
            throw new InvalidArgumentException('Every role has to belong to the realm of the user.');
        }

        DB::transaction(function () use ($user, $after): void {
            $before = $user->roles()->get();

            $added = $after->whereNotIn('id', $before->modelKeys())->map(fn (Role $role): string => $role->name)->sort()->values()->all();
            $removed = $before->whereNotIn('id', $after->modelKeys())->map(fn (Role $role): string => $role->name)->sort()->values()->all();

            $user->roles()->sync($after->modelKeys());

            if ($added !== [] || $removed !== []) {
                Audit::record(RoleAuditEvent::AssignmentsUpdated, $user, $user->realm, [
                    'realm' => $user->realm->slug,
                    'added' => $added,
                    'removed' => $removed,
                ]);
            }
        });
    }
}
