<?php

declare(strict_types=1);

use App\Auth\Support\OidcScopeCatalog;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\RecoveryCodeProvider;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\TotpFactorProvider;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\WebAuthnFactorProvider;

/*
 * Only the keys this app changes from the laravel-oidc package defaults live
 * here; everything else (signing keys, token lifetimes, claims, social
 * providers, sessions, and the whole route/handler table) is inherited from the
 * package config via mergeConfigFrom.
 */
return [
    'issuer' => env('OIDC_ISSUER', env('APP_URL')),

    'login_route' => env('OIDC_LOGIN_ROUTE', 'identity.login'),

    'first_party' => [
        'client_id' => env('OIDC_FIRST_PARTY_CLIENT') ?: null,
        'trusted' => (bool) env('OIDC_FIRST_PARTY_TRUSTED', true),
    ],

    'auth' => [
        'guard' => env('OIDC_AUTH_GUARD', 'identity'),
        'provider' => env('OIDC_AUTH_PROVIDER', 'users'),
        'home' => env('OIDC_AUTH_HOME', '/login'),
        'username' => env('OIDC_AUTH_USERNAME', 'email'),
        'two_factor' => [
            // A passkey satisfies a second-factor challenge as well as it signs
            // the user in. Leaving it out here made the profile call a
            // passkey-only account protected while the login pipeline never
            // asked for anything beyond the password.
            'challenge_providers' => ['totp', 'webauthn'],
            'secret_length' => 16,
            'window' => 1,
            'recovery_codes' => 8,
        ],
        'factors' => [
            TotpFactorProvider::class,
            RecoveryCodeProvider::class,
            WebAuthnFactorProvider::class,
        ],
    ],

    'scopes' => [
        'catalog' => OidcScopeCatalog::class,
    ],
];
