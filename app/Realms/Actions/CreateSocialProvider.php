<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesSocialProvider;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Enums\SocialProviderDriver;
use Illuminate\Support\Facades\DB;

final class CreateSocialProvider implements CreatesSocialProvider
{
    /** @param array<string, string> $config */
    public function handle(Realm $realm, string $key, SocialProviderDriver $driver, array $config, bool $enabled): RealmSocialProvider
    {
        return DB::transaction(function () use ($realm, $key, $driver, $config, $enabled): RealmSocialProvider {
            $provider = $realm->socialProviders()->create([
                'key' => $key,
                'driver' => $driver,
                'enabled' => $enabled,
                'config' => array_intersect_key($config, array_flip($driver->fields())),
            ]);

            Audit::record(RealmAuditEvent::SocialProviderCreated, $provider, $realm, [
                'realm' => $realm->slug,
                'key' => $provider->key,
                'driver' => $driver->value,
                'enabled' => $provider->enabled,
            ]);

            return $provider;
        });
    }
}
