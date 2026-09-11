<?php

declare(strict_types=1);

/*
 * Only the keys this app changes from the laravel-oidc-client package defaults
 * live here; the rest (client id/secret, scopes, guard, back-channel logout,
 * and the route table) is inherited from the package config via mergeConfigFrom.
 */
return [
    'enabled' => (bool) env('OIDC_RP_ENABLED', true),

    'issuer' => env('OIDC_RP_ISSUER', rtrim((string) env('APP_URL'), '/')),

    // The console is its own relying party against its own first-party client,
    // so one pair of credentials configures both sides. Set the OIDC_RP_*
    // variables only to point the console at a different provider.
    'client_id' => env('OIDC_RP_CLIENT_ID') ?: env('OIDC_FIRST_PARTY_CLIENT'),

    'client_secret' => env('OIDC_RP_CLIENT_SECRET') ?: env('OIDC_FIRST_PARTY_SECRET'),

    // The package has no default: RelyingParty sends this string verbatim,
    // so an empty value breaks the authorize request.
    'redirect_uri' => env('OIDC_RP_REDIRECT_URI', env('APP_URL').'/login/callback'),

    'redirect_after_login' => env('OIDC_RP_HOME', '/'),

    'post_logout_redirect_uri' => env('OIDC_RP_POST_LOGOUT_REDIRECT_URI', env('APP_URL')),
];
