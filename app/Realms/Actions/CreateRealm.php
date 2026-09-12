<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Data\RealmSettings;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Support\RealmConfiguration;
use Bambamboole\LaravelOidc\Server\Shared\SigningKeys\SigningKeyStore;
use Bambamboole\LaravelOidc\Server\SigningKeys\SigningKeyGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateRealm implements CreatesRealm
{
    public function __construct(
        private SigningKeyGenerator $keys,
        private SigningKeyStore $store,
    ) {}

    /**
     * A realm without a signing key can neither mint a token nor serve a JWKS,
     * so it gets its own keypair here.
     *
     * The configuration is resolved and validated before the row exists, which
     * is what keeps a configuration the rules reject from leaving a realm
     * behind.
     *
     * @param  array<string, mixed>  $configuration
     */
    public function handle(string $name, string $domain, string $slug, array $configuration = []): Realm
    {
        $settings = $this->resolveSettings($configuration, $name, $slug);
        $deviations = $settings->deviations();

        return DB::transaction(function () use ($name, $domain, $slug, $settings, $deviations): Realm {
            $realm = Realm::create(['name' => $name, 'slug' => $slug, 'domain' => $domain, 'settings' => $settings]);

            $realm->runAsCurrent(fn () => $this->store->rotate($this->keys->generate()));

            Audit::record(RealmAuditEvent::RealmCreated, $realm, properties: $deviations === [] ? [] : ['configuration' => $deviations]);

            return $realm;
        });
    }

    /**
     * A new realm has no configuration to merge a submission into, so the
     * defaults stand in for one: the submission is validated against the whole
     * set, which is where the rules comparing two settings find the values it
     * left out, and the DTO normalises every value to the type its setting
     * holds.
     *
     * @param  array<string, mixed>  $configuration
     */
    private function resolveSettings(array $configuration, string $name, string $slug): RealmSettings
    {
        $defaults = RealmConfiguration::defaults();
        $submitted = array_intersect_key($configuration, $defaults);

        if ($submitted === []) {
            return RealmSettings::defaults();
        }

        $validated = RealmConfiguration::validate(['name' => $name, ...$defaults, ...$submitted], $slug);

        return RealmSettings::defaults()->merge(array_intersect_key($validated, $submitted));
    }
}
