<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Data\UpdateSocialProviderData;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Enums\RealmAdminEvent;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final class UpdateSocialProvider
{
    public function handle(RealmSocialProvider $provider, UpdateSocialProviderData $data): void
    {
        DB::transaction(function () use ($provider, $data): void {
            $provider->refresh();
            $config = $data->config instanceof Optional ? [] : $data->config;
            $enabled = $data->enabled instanceof Optional ? $provider->enabled : $data->enabled;
            $driver = $provider->driver;
            $secrets = $driver->secrets();
            $stored = $provider->config;

            $merged = $stored;
            $changes = [];

            foreach ($driver->fields() as $field) {
                if (! array_key_exists($field, $config)) {
                    continue;
                }

                $value = $config[$field];

                if (in_array($field, $secrets, true) && $value === '') {
                    continue;
                }

                if (($stored[$field] ?? null) === $value) {
                    continue;
                }

                $merged[$field] = $value;
                $changes[$field] = in_array($field, $secrets, true)
                    ? ['replaced' => true]
                    : ['old' => $stored[$field] ?? null, 'new' => $value];
            }

            if ($provider->enabled !== $enabled) {
                $changes['enabled'] = ['old' => $provider->enabled, 'new' => $enabled];
            }

            if ($changes === []) {
                return;
            }

            $provider->fill([
                'enabled' => $enabled,
                'config' => array_intersect_key($merged, array_flip($driver->fields())),
            ])->save();

            Audit::record(RealmAdminEvent::SocialProviderUpdated, $provider, $provider->realm, [
                'realm' => $provider->realm->slug,
                'key' => $provider->key,
                'changes' => $changes,
            ]);
        });
    }
}
