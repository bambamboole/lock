<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Events\UserDeleting;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Enums\RealmAdminEvent;
use App\Shared\Realms\Events\RealmDeleting;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRealm implements DeletesRealm
{
    /** The row goes for good, so a deleted realm's slug is free again. */
    public function handle(Realm $realm): void
    {
        DB::transaction(function () use ($realm): void {
            $realm->roles()->delete();
            $realm->users()->each(function (User $user): void {
                event(new UserDeleting($user));
                $user->delete();
            });

            event(new RealmDeleting($realm->slug));

            $realm->delete();

            Audit::record(RealmAdminEvent::RealmDeleted, $realm, context: ['name' => $realm->name, 'slug' => $realm->slug]);
        });
    }
}
