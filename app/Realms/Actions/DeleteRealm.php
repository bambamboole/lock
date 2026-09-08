<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use Illuminate\Support\Facades\DB;

final class DeleteRealm
{
    /**
     * A realm's users have no identity outside it, so deleting the realm
     * deletes them with it — there is nowhere to move them to. The realm row
     * itself is soft-deleted so the audit trail keeps resolving its name.
     */
    public function handle(Realm $realm): void
    {
        DB::transaction(function () use ($realm): void {
            $realm->invitations()->delete();
            $realm->users()->each(fn ($user) => $user->delete());
            $realm->roles()->delete();
            $realm->delete();
        });
    }
}
