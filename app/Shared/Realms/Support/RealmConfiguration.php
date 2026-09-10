<?php

declare(strict_types=1);

namespace App\Shared\Realms\Support;

use App\Shared\Settings\SettingsRegistry;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ScopeRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ClientSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\CredentialSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\SessionSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\TokenSettings;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

final class RealmConfiguration
{
    /** @return array<string, int|bool|string|list<string>> */
    public static function defaults(): array
    {
        $tokens = new TokenSettings;
        $sessions = new SessionSettings;
        $credentials = new CredentialSettings;
        $clients = new ClientSettings;

        return [
            'access_token_lifetime' => $tokens->accessTokenLifetime,
            'id_token_lifetime' => $tokens->idTokenLifetime,
            'client_credentials_lifetime' => $tokens->clientCredentialsLifetime,
            'refresh_token_lifetime' => $tokens->refreshTokenLifetime,
            'session_cookie_name' => '',
            'session_absolute_lifetime' => $sessions->absoluteLifetime,
            'session_token_ttl' => $sessions->tokenTtl,
            'session_token_refresh_skew' => $sessions->tokenRefreshSkew,
            'challenge_providers' => $credentials->challengeProviders,
            'totp_secret_length' => $credentials->totpSecretLength,
            'totp_window' => $credentials->totpWindow,
            'recovery_codes' => $credentials->recoveryCodes,
            'dynamic_registration' => $clients->dynamicRegistration,
            'allowed_redirect_schemes' => $clients->allowedRedirectSchemes,
            'allowed_redirect_domains' => $clients->allowedRedirectDomains,
            'default_scopes' => $clients->defaultScopes,
            'optional_scopes' => $clients->optionalScopes,
            'token_exchange' => $clients->tokenExchange,
            'first_party_trusted' => $clients->firstPartyTrusted,
            'trusted_clients' => $clients->trustedClients,
        ];
    }

    public static function register(SettingsRegistry $registry): void
    {
        foreach (self::defaults() as $key => $default) {
            $definition = $registry->define('realm.'.$key)->default($default);

            match (true) {
                is_int($default) => $definition->integer(),
                is_bool($default) => $definition->boolean(),
                is_string($default) => $definition->string(),
                default => $definition->array(),
            };
        }

        $registry->define('realm.first_party_client_id')->string();
    }

    /** @return array<string, list<string|In>> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'access_token_lifetime' => ['required', 'integer', 'min:1', 'max:31536000'],
            'id_token_lifetime' => ['required', 'integer', 'min:1', 'max:31536000'],
            'client_credentials_lifetime' => ['required', 'integer', 'min:1', 'max:31536000'],
            'refresh_token_lifetime' => ['required', 'integer', 'min:1', 'max:31536000'],
            'session_cookie_name' => ['nullable', 'string', 'max:128', 'regex:/\A[A-Za-z0-9_-]+\z/', 'not_in:'.config('session.cookie')],
            'session_absolute_lifetime' => ['required', 'integer', 'min:1', 'max:31536000'],
            'session_token_ttl' => ['required', 'integer', 'min:1', 'max:31536000', 'lte:session_absolute_lifetime'],
            'session_token_refresh_skew' => ['required', 'integer', 'min:0', 'lt:session_token_ttl'],
            'challenge_providers' => ['required', 'array', 'list', 'min:1', 'max:2'],
            'challenge_providers.*' => ['required', 'string', 'distinct', Rule::in(['totp', 'webauthn'])],
            'totp_secret_length' => ['required', 'integer', 'min:16', 'max:64', 'multiple_of:8'],
            'totp_window' => ['required', 'integer', 'min:0', 'max:10'],
            'recovery_codes' => ['required', 'integer', 'min:1', 'max:20'],
            'dynamic_registration' => ['required', 'boolean'],
            'allowed_redirect_schemes' => ['present', 'array', 'list', 'max:100'],
            'allowed_redirect_schemes.*' => ['required', 'string', 'max:64', 'distinct', 'regex:/^[a-z][a-z0-9+.-]*$/', 'not_in:javascript,data,vbscript,file'],
            'allowed_redirect_domains' => ['present', 'array', 'list', 'max:100'],
            'allowed_redirect_domains.*' => ['required', 'string', 'max:253', 'distinct', 'regex:/\A(\*|[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\[[a-f0-9:]+\])\z/'],
            'default_scopes' => ['present', 'array', 'list', 'max:100'],
            'default_scopes.*' => ['required', 'string', 'max:255', 'distinct', Rule::in(self::catalogScopes())],
            'optional_scopes' => ['present', 'array', 'list', 'max:100'],
            'optional_scopes.*' => ['required', 'string', 'max:255', 'distinct', Rule::in(['*', ...self::catalogScopes()])],
            'token_exchange' => ['required', 'boolean'],
            'first_party_trusted' => ['required', 'boolean'],
            'trusted_clients' => ['present', 'array', 'list', 'max:100'],
            'trusted_clients.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }

    /** @return list<string> */
    private static function catalogScopes(): array
    {
        return array_values(array_map(strval(...), app(ScopeRepository::class)->all()->pluck('id')->all()));
    }
}
