<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\RealmSocialProvider;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesSocialProvider;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Illuminate\Support\Facades\DB;

final class UpdateSocialProvider implements UpdatesSocialProvider
{
    /** @param array<string, string> $config */
    public function handle(RealmSocialProvider $provider, array $config, bool $enabled): void
    {
        DB::transaction(function () use ($provider, $config, $enabled): void {
            $driver = $provider->driver;
            $secrets = $driver->secrets();
            $stored = $provider->config;

            $merged = $stored;
            $changes = [];

            foreach ($driver->fields() as $field) {
                $value = $config[$field] ?? '';

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

            Audit::record(RealmAuditEvent::SocialProviderUpdated, $provider, $provider->realm, [
                'realm' => $provider->realm->slug,
                'key' => $provider->key,
                'changes' => $changes,
            ]);
        });
    }
}
