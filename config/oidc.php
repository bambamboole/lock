<?php
declare(strict_types=1);

use App\Auth\Support\OidcScopeCatalog;
use Bambamboole\LaravelOidc\Server\Credentials\RecoveryCodeProvider;
use Bambamboole\LaravelOidc\Server\Credentials\TotpFactorProvider;
use Bambamboole\LaravelOidc\Server\Credentials\WebAuthnFactorProvider;

return [
    'realm' => env('REALM_MASTER_SLUG', 'admin'),
    'issuer' => env('OIDC_ISSUER', env('APP_URL')),

    'routes' => [
        'middleware' => [],
        'realms' => 'path',
    ],

    // mergeConfigFrom replaces the auth array, so retain its defaults when overriding home.
    'auth' => [
        'guard' => env('OIDC_AUTH_GUARD', 'identity'),
        'provider' => env('OIDC_AUTH_PROVIDER', 'users'),
        'api_guard' => env('OIDC_API_GUARD', 'oidc'),
        'login_route' => env('OIDC_LOGIN_ROUTE', 'identity.login'),
        'logout_redirect' => '/',
        'home' => env('OIDC_AUTH_HOME', '/login'),
        'username' => env('OIDC_AUTH_USERNAME', 'email'),
        'acr_values' => [
            'single_factor' => '1',
            'multi_factor' => '2',
        ],
        'password' => [
            'min_length' => 8,
            'mixed_case' => false,
            'numbers' => false,
            'symbols' => false,
            'uncompromised' => false,
            'history' => 0,
            'max_age_days' => null,
        ],
        'factors' => [
            TotpFactorProvider::class,
            RecoveryCodeProvider::class,
            WebAuthnFactorProvider::class,
        ],
    ],

    // The package's whole `clients` block would be replaced, not merged, by a
    // `clients` key here — mergeConfigFrom only merges the top level — so the
    // secret `app:bootstrap` reconciles the console client to gets its own key
    // next to the package's OIDC_FIRST_PARTY_CLIENT and OIDC_FIRST_PARTY_TRUSTED.
    'first_party_secret' => env('OIDC_FIRST_PARTY_SECRET'),

    'scopes' => [
        'catalog' => OidcScopeCatalog::class,
    ],
];
