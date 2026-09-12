<?php
declare(strict_types=1);

namespace App\Roles\Actions;

use App\Roles\Models\Role;
use App\Shared\Audit\Audit;
use App\Shared\Roles\Contracts\DeletesRole;
use App\Shared\Roles\Enums\RoleAdminEvent;
use Illuminate\Support\Facades\DB;

final class DeleteRole implements DeletesRole
{
    public function handle(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $detached = $role->users()->detach();

            $role->delete();

            Audit::record(RoleAdminEvent::RoleDeleted, $role, $role->realm, [
                'realm' => $role->realm->slug,
                'name' => $role->name,
                'users-detached' => $detached,
            ]);
        });
    }
}
