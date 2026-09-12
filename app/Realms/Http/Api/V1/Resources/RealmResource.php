<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Resources;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Realm
 */
final class RealmResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** Stable identifier of the realm, and its path in this API. */
            'slug' => $this->slug,
            /** Human readable name shown to its users. */
            'name' => $this->name,
            /** The host the realm is served from. */
            'host' => $this->host(),
            /** The realm's OIDC issuer, the `iss` of every token it mints. */
            'issuer' => $this->origin(),
            /** The configured domain; null for the master realm, which follows APP_URL. */
            'domain' => $this->domain,
            /** Whether the configured domain was seen to reach this instance. */
            'domain_status' => $this->domain_status,
            /** When the domain was last checked. */
            'domain_checked_at' => $this->domain_checked_at?->toIso8601String(),
            /** Why the last domain check failed, when it did. */
            'domain_check_error' => $this->domain_check_error,
            /** Whether this is the administration realm, which cannot be deleted or re-pointed. */
            'master' => $this->isMaster(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'configuration' => $this->configuration(),
        ];
    }

    /**
     * The realm's own policy, under the names the update accepts. Written out
     * rather than looped so the generated schema carries the type and meaning of
     * every setting; `RealmApiTest` compares these keys against
     * {@see RealmConfiguration::defaults()} and fails when one is missing.
     *
     * @return array<string, mixed>
     */
    private function configuration(): array
    {
        $configuration = $this->resource->configuration();

        return [
            /** Seconds an access token stays valid. */
            'access_token_lifetime' => (int) $configuration['access_token_lifetime'],
            /** Seconds an ID token stays valid. */
            'id_token_lifetime' => (int) $configuration['id_token_lifetime'],
            /** Seconds a token issued to a machine client stays valid. */
            'client_credentials_lifetime' => (int) $configuration['client_credentials_lifetime'],
            /** Seconds a refresh token stays valid. */
            'refresh_token_lifetime' => (int) $configuration['refresh_token_lifetime'],
            /** Seconds a sign-in session lives at most, however often it is refreshed. */
            'session_absolute_lifetime' => (int) $configuration['session_absolute_lifetime'],
            /** Seconds a session token stays valid before it is refreshed. */
            'session_token_ttl' => (int) $configuration['session_token_ttl'],
            /** Seconds before expiry at which a session token is refreshed early. */
            'session_token_refresh_skew' => (int) $configuration['session_token_refresh_skew'],
            /** The ways an identity may sign in: `password`, `passkey`, `social`. */
            'login_methods' => self::strings($configuration['login_methods']),
            /** Whether an unverified address blocks sign-in. */
            'email_verification_required' => (bool) $configuration['email_verification_required'],
            /** When a second factor is demanded: `never`, `if_enrolled`, `always`. */
            'mfa_requirement' => (string) $configuration['mfa_requirement'],
            /** The second factors on offer: `totp`, `webauthn`. */
            'challenge_providers' => self::strings($configuration['challenge_providers']),
            /** Bytes of a generated TOTP secret. */
            'totp_secret_length' => (int) $configuration['totp_secret_length'],
            /** Time steps either side of now a TOTP code is accepted in. */
            'totp_window' => (int) $configuration['totp_window'],
            /** How many recovery codes an identity is issued. */
            'recovery_codes' => (int) $configuration['recovery_codes'],
            /** Shortest password accepted. */
            'password_min_length' => (int) $configuration['password_min_length'],
            /** Whether a password must mix upper and lower case. */
            'password_mixed_case' => (bool) $configuration['password_mixed_case'],
            /** Whether a password must carry a digit. */
            'password_numbers' => (bool) $configuration['password_numbers'],
            /** Whether a password must carry a symbol. */
            'password_symbols' => (bool) $configuration['password_symbols'],
            /** Whether a password is checked against known breaches. */
            'password_uncompromised' => (bool) $configuration['password_uncompromised'],
            /** How many previous passwords may not be reused. */
            'password_history' => (int) $configuration['password_history'],
            /** Days before a password must be changed; `0` never expires. */
            'password_max_age_days' => (int) $configuration['password_max_age_days'],
            /** Whether a client may register itself through RFC 7591. */
            'dynamic_registration' => (bool) $configuration['dynamic_registration'],
            /** URI schemes a redirect URI may use. */
            'allowed_redirect_schemes' => self::strings($configuration['allowed_redirect_schemes']),
            /** Hosts a redirect URI may point at; `*` allows any. */
            'allowed_redirect_domains' => self::strings($configuration['allowed_redirect_domains']),
            /** Scopes every client is granted without asking. */
            'default_scopes' => self::strings($configuration['default_scopes']),
            /** Scopes a client may request on top; `*` allows any of the catalog. */
            'optional_scopes' => self::strings($configuration['optional_scopes']),
            /** Whether the RFC 8693 token exchange grant is served. */
            'token_exchange' => (bool) $configuration['token_exchange'],
            /** Whether the first-party client skips the consent screen. */
            'first_party_trusted' => (bool) $configuration['first_party_trusted'],
            /** Client ids that skip the consent screen. */
            'trusted_clients' => self::strings($configuration['trusted_clients']),
        ];
    }

    /** @return list<string> */
    private static function strings(mixed $value): array
    {
        return array_values(array_filter(is_array($value) ? $value : [], is_string(...)));
    }
}
