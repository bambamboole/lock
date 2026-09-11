<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Purge\PurgeRealm;
use Bambamboole\LaravelOidc\Server\Purge\PurgeUser;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRealm implements DeletesRealm
{
    public function __construct(
        private PurgeUser $purgeUser,
        private PurgeRealm $purgeRealm,
    ) {}

    /** The row goes for good, so a deleted realm's slug is free again. */
    public function handle(Realm $realm): void
    {
        DB::transaction(function () use ($realm): void {
            $realm->realmRoles()->delete();
            $realm->users()->each(function (User $user): void {
                ($this->purgeUser)($user);
                $user->delete();
            });

            ($this->purgeRealm)($realm->slug);

            $realm->delete();

            Audit::record(RealmAuditEvent::RealmDeleted, $realm, properties: ['name' => $realm->name, 'slug' => $realm->slug]);
        });
    }
}
