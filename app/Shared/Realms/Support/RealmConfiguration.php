<?php

declare(strict_types=1);

namespace App\Shared\Realms\Support;

use App\Shared\Realms\Data\RealmSetting;
use App\Shared\Realms\Enums\RealmSettingSection;
use App\Shared\Realms\Enums\RealmSettingType;
use App\Shared\Settings\SettingsRegistry;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ScopeRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ClientSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\CredentialSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\PasswordPolicy;
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
        $password = PasswordPolicy::fromConfig();

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
            'password_min_length' => $password->minLength,
            'password_mixed_case' => $password->mixedCase,
            'password_numbers' => $password->numbers,
            'password_symbols' => $password->symbols,
            'password_uncompromised' => $password->uncompromised,
            'password_history' => $password->history,
            'password_max_age_days' => $password->maxAgeDays ?? 0,
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

    /**
     * Every configurable setting with the section it belongs to and the kind
     * of value it holds — the one place the admin console reads to build both
     * a section's rows and the input that edits a single one of them.
     *
     * @return array<string, RealmSetting>
     */
    public static function settings(): array
    {
        $settings = [];

        foreach (self::sectionTypes() as $section => $types) {
            foreach ($types as $name => $type) {
                $settings[$name] = new RealmSetting($name, RealmSettingSection::from($section), $type);
            }
        }

        return $settings;
    }

    public static function setting(string $name): ?RealmSetting
    {
        return self::settings()[$name] ?? null;
    }

    /**
     * @return array<string, list<RealmSetting>>
     */
    public static function sections(): array
    {
        $sections = [];

        foreach (self::settings() as $setting) {
            $sections[$setting->section->value][] = $setting;
        }

        return $sections;
    }

    /**
     * Field labels for the validator, so a rule that fails on a setting the
     * user is not looking at still names it the way the console does.
     *
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        $names = ['name' => __('admin.realms.fields.name.label')];

        foreach (self::settings() as $setting) {
            $names[$setting->name] = __('admin.realms.fields.'.$setting->translationKey().'.label');
        }

        return $names;
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
            'password_min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'password_mixed_case' => ['required', 'boolean'],
            'password_numbers' => ['required', 'boolean'],
            'password_symbols' => ['required', 'boolean'],
            'password_uncompromised' => ['required', 'boolean'],
            'password_history' => ['required', 'integer', 'min:0', 'max:24'],
            'password_max_age_days' => ['required', 'integer', 'min:0', 'max:3650'],
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

    /**
     * @return array<string, array<string, RealmSettingType>>
     */
    private static function sectionTypes(): array
    {
        return [
            'tokens' => [
                'access_token_lifetime' => RealmSettingType::Duration,
                'id_token_lifetime' => RealmSettingType::Duration,
                'client_credentials_lifetime' => RealmSettingType::Duration,
                'refresh_token_lifetime' => RealmSettingType::Duration,
            ],
            'sessions' => [
                'session_cookie_name' => RealmSettingType::Text,
                'session_absolute_lifetime' => RealmSettingType::Duration,
                'session_token_ttl' => RealmSettingType::Duration,
                'session_token_refresh_skew' => RealmSettingType::Duration,
            ],
            'mfa' => [
                'challenge_providers' => RealmSettingType::Factors,
                'totp_secret_length' => RealmSettingType::Count,
                'totp_window' => RealmSettingType::Count,
                'recovery_codes' => RealmSettingType::Count,
            ],
            'passwords' => [
                'password_min_length' => RealmSettingType::Count,
                'password_history' => RealmSettingType::Count,
                'password_max_age_days' => RealmSettingType::Days,
                'password_mixed_case' => RealmSettingType::Flag,
                'password_numbers' => RealmSettingType::Flag,
                'password_symbols' => RealmSettingType::Flag,
                'password_uncompromised' => RealmSettingType::Flag,
            ],
            'clients' => [
                'dynamic_registration' => RealmSettingType::Flag,
                'token_exchange' => RealmSettingType::Flag,
                'first_party_trusted' => RealmSettingType::Flag,
                'allowed_redirect_schemes' => RealmSettingType::StringList,
                'allowed_redirect_domains' => RealmSettingType::StringList,
                'default_scopes' => RealmSettingType::StringList,
                'optional_scopes' => RealmSettingType::StringList,
                'trusted_clients' => RealmSettingType::StringList,
            ],
        ];
    }

    /** @return list<string> */
    private static function catalogScopes(): array
    {
        return array_values(array_map(strval(...), app(ScopeRepository::class)->all()->pluck('id')->all()));
    }
}
