<?php
declare(strict_types=1);

use App\Realms\Http\Middleware\SetCurrentRealm;
use App\Resources\Support\ResourceScopeCatalog;
use App\Shared\Audit\Support\DatabaseAuditSink;
use Bambamboole\LaravelOidc\Server\Credentials\RecoveryCodeProvider;
use Bambamboole\LaravelOidc\Server\Credentials\TotpFactorProvider;
use Bambamboole\LaravelOidc\Server\Credentials\WebAuthnFactorProvider;

return [
    'realm' => env('REALM_MASTER_SLUG', 'admin'),
    'issuer' => env('OIDC_ISSUER', env('APP_URL')),

    'routes' => [
        // The protocol endpoints outside the web group mint URLs too (the
        // discovery document), and those have to name the realm's own host.
        'middleware' => [SetCurrentRealm::class],
        // Every realm is served from its own host; the master realm from APP_URL's.
        'realms' => 'domain',
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

    // mergeConfigFrom only merges the top level, so naming a sink here drops
    // the package's other audit keys unless they are repeated.
    'audit' => [
        'enabled' => env('OIDC_AUDIT_ENABLED', true),
        'sink' => DatabaseAuditSink::class,
        'log_channel' => env('OIDC_AUDIT_LOG_CHANNEL'),
    ],

    // A scope belongs to exactly one resource of the realm, so the catalog is
    // read per requested RFC 8707 `resource` rather than configured statically.
    'scopes' => [
        'catalog' => ResourceScopeCatalog::class,
    ],
];
