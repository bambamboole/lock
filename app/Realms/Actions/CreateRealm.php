<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Enums\RealmAuditEvent;
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
     */
    public function handle(string $name, string $domain, ?string $slug = null): Realm
    {
        return DB::transaction(function () use ($name, $domain, $slug): Realm {
            $realm = Realm::create(array_filter([
                'name' => $name,
                'slug' => $slug,
                'domain' => strtolower($domain),
            ]));

            $realm->runAsCurrent(fn () => $this->store->rotate($this->keys->generate()));

            Audit::record(RealmAuditEvent::RealmCreated, $realm);

            return $realm;
        });
    }
}
