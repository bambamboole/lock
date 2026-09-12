<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\RealmSocialProvider;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesSocialProvider;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Brokering\Models\SocialAccount;
use Illuminate\Support\Facades\DB;

final class DeleteSocialProvider implements DeletesSocialProvider
{
    public function handle(RealmSocialProvider $provider): void
    {
        DB::transaction(function () use ($provider): void {
            $realm = $provider->realm;

            /*
             * The links go with the provider: a key that is added back later
             * would otherwise silently restore logins the administrator
             * removed.
             */
            $unlinked = SocialAccount::query()
                ->inRealm($realm->slug)
                ->where('provider', $provider->key)
                ->delete();

            $provider->delete();

            Audit::record(RealmAuditEvent::SocialProviderDeleted, $provider, $realm, [
                'realm' => $realm->slug,
                'key' => $provider->key,
                'driver' => $provider->driver->value,
                'accounts-unlinked' => $unlinked,
            ]);
        });
    }
}
