<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\RealmRole;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesRealmRole;
use Illuminate\Support\Facades\DB;

final class DeleteRealmRole implements DeletesRealmRole
{
    public function handle(RealmRole $role): void
    {
        DB::transaction(function () use ($role): void {
            $detached = $role->users()->detach();

            $role->delete();

            Audit::record(RealmAuditEvent::RoleDeleted, $role, properties: [
                'realm' => $role->realm->slug,
                'name' => $role->name,
                'users-detached' => $detached,
            ]);
        });
    }
}
