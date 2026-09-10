<?php
declare(strict_types=1);

namespace App\Realms\Models;

use App\Auth\Models\User;
use App\Realms\Observers\RealmObserver;
use App\Shared\Realms\Support\RealmConfiguration;
use App\Shared\Settings\Concerns\HasSettings;
use App\Shared\Settings\Contracts\ProvidesSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Realm as RealmContract;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\BrokeringSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ClientSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\CredentialSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\KeySettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\LoginSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ScopeSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\SessionSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\TokenSettings;
use Carbon\CarbonImmutable;
use Database\Factories\RealmFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Context;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\RealmFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Realm whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug'])]
#[ObservedBy(RealmObserver::class)]
#[RouteKey('slug')]
final class Realm extends Model implements ProvidesSettings, RealmContract
{
    /** @use HasFactory<RealmFactory> */
    use HasFactory, HasSettings, HasUuids;

    public static function master(): self
    {
        return self::where('slug', config('realm.master_slug'))->firstOrFail();
    }

    public function isMaster(): bool
    {
        return $this->slug === config('realm.master_slug');
    }

    public static function fromContext(): ?self
    {
        $realmId = self::currentId();

        return $realmId === null ? null : self::find($realmId);
    }

    /**
     * The current realm's key without loading it — for callers that only need
     * to scope a query and would otherwise pay a lookup per call.
     */
    public static function currentId(): ?string
    {
        $realmId = Context::get('realm_id');

        return is_string($realmId) || is_int($realmId) ? (string) $realmId : null;
    }

    /**
     * Context is dehydrated into queued jobs, so anything dispatched while this
     * realm is current can resolve it later via Realm::fromContext().
     */
    public function makeCurrent(): void
    {
        Context::add('realm_id', $this->id);
    }

    /**
     * Escape hatch for schedulers, queue workers and maintenance scripts that
     * start without a request-bound realm. Restores the previous context, so
     * nesting a run inside another realm's is safe.
     */
    public function runAsCurrent(callable $callback): mixed
    {
        $previousRealmId = Context::get('realm_id');
        $this->makeCurrent();

        try {
            return $callback();
        } finally {
            if ($previousRealmId === null) {
                Context::forget('realm_id');
            } else {
                Context::add('realm_id', $previousRealmId);
            }
        }
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['id' => 'string'];
    }

    public function id(): string
    {
        return $this->slug;
    }

    /** @return array<string, mixed> */
    public function configuration(): array
    {
        $settings = $this->settings;
        $configuration = [];

        foreach (array_keys(RealmConfiguration::defaults()) as $key) {
            $configuration[$key] = $settings->get('realm.'.$key);
        }

        return $configuration;
    }

    public function tokens(): TokenSettings
    {
        $settings = $this->settings;

        return new TokenSettings(
            accessTokenLifetime: $settings->integer('realm.access_token_lifetime'),
            idTokenLifetime: $settings->integer('realm.id_token_lifetime'),
            clientCredentialsLifetime: $settings->integer('realm.client_credentials_lifetime'),
            refreshTokenLifetime: $settings->integer('realm.refresh_token_lifetime'),
        );
    }

    public function sessions(): SessionSettings
    {
        $settings = $this->settings;

        return new SessionSettings(
            absoluteLifetime: $settings->integer('realm.session_absolute_lifetime'),
            tokenTtl: $settings->integer('realm.session_token_ttl'),
            tokenRefreshSkew: $settings->integer('realm.session_token_refresh_skew'),
            cookieName: $settings->string('realm.session_cookie_name') ?: null,
        );
    }

    public function credentials(): CredentialSettings
    {
        $settings = $this->settings;

        return new CredentialSettings(
            challengeProviders: $this->stringList('challenge_providers'),
            totpSecretLength: $settings->integer('realm.totp_secret_length'),
            totpWindow: $settings->integer('realm.totp_window'),
            recoveryCodes: $settings->integer('realm.recovery_codes'),
        );
    }

    public function clients(): ClientSettings
    {
        $settings = $this->settings;

        return new ClientSettings(
            dynamicRegistration: $settings->boolean('realm.dynamic_registration'),
            allowedRedirectSchemes: $this->stringList('allowed_redirect_schemes'),
            allowedRedirectDomains: $this->stringList('allowed_redirect_domains'),
            defaultScopes: $this->stringList('default_scopes'),
            optionalScopes: $this->stringList('optional_scopes'),
            tokenExchange: $settings->boolean('realm.token_exchange'),
            firstPartyClientId: $settings->string('realm.first_party_client_id') ?: null,
            firstPartyTrusted: $settings->boolean('realm.first_party_trusted'),
            trustedClients: $this->stringList('trusted_clients'),
        );
    }

    public function login(): LoginSettings
    {
        return LoginSettings::fromConfig();
    }

    public function brokering(): BrokeringSettings
    {
        return BrokeringSettings::fromConfig();
    }

    public function scopes(): ScopeSettings
    {
        return ScopeSettings::fromConfig();
    }

    public function keys(): KeySettings
    {
        return KeySettings::fromConfig();
    }

    /** @return list<string> */
    private function stringList(string $key): array
    {
        return array_values(array_filter($this->settings->array('realm.'.$key), is_string(...)));
    }
}
