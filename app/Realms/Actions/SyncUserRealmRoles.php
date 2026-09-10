<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\RealmRole;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\SyncsUserRealmRoles;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SyncUserRealmRoles implements SyncsUserRealmRoles
{
    public function handle(User $user, array $roleIds): void
    {
        $roleIds = array_values(array_unique($roleIds));
        $after = $user->realm->realmRoles()->findMany($roleIds);

        if ($after->count() !== count($roleIds)) {
            throw new InvalidArgumentException('Every role has to belong to the realm of the user.');
        }

        DB::transaction(function () use ($user, $after): void {
            $before = $user->realmRoles()->get();

            $added = $after->whereNotIn('id', $before->modelKeys())->map(fn (RealmRole $role): string => $role->name)->sort()->values()->all();
            $removed = $before->whereNotIn('id', $after->modelKeys())->map(fn (RealmRole $role): string => $role->name)->sort()->values()->all();

            $user->realmRoles()->sync($after->modelKeys());

            if ($added !== [] || $removed !== []) {
                Audit::record(RealmAuditEvent::UserRolesUpdated, $user, properties: [
                    'realm' => $user->realm->slug,
                    'added' => $added,
                    'removed' => $removed,
                ]);
            }
        });
    }
}
