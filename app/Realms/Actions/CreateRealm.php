<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesRealm;
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
     * Creates the realm with the default configuration. The realm starts
     * empty: its users register or are created inside it afterwards.
     *
     * Signing keys are per realm and live in `oidc_signing_keys`, so a realm
     * without one can neither mint a token nor serve a JWKS. It gets its own
     * keypair here rather than sharing the instance's.
     */
    public function handle(string $name, ?string $slug = null): Realm
    {
        return DB::transaction(function () use ($name, $slug): Realm {
            $realm = Realm::create(array_filter([
                'name' => $name,
                'slug' => $slug,
            ]));

            $realm->runAsCurrent(fn () => $this->store->rotate($this->keys->generate()));

            Audit::record(RealmAuditEvent::RealmCreated, $realm);

            return $realm;
        });
    }
}
