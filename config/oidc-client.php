<?php

declare(strict_types=1);

/*
 * Only the keys this app changes from the laravel-oidc-client package defaults
 * live here; the rest (client id/secret, scopes, guard, back-channel logout,
 * and the route table) is inherited from the package config via mergeConfigFrom.
 */
return [
    'enabled' => (bool) env('OIDC_RP_ENABLED', true),

    'issuer' => env('OIDC_RP_ISSUER', env('APP_URL')),

    // The package has no default: RelyingParty sends this string verbatim,
    // so an empty value breaks the authorize request.
    'redirect_uri' => env('OIDC_RP_REDIRECT_URI', env('APP_URL').'/login/callback'),

    'post_logout_redirect_uri' => env('OIDC_RP_POST_LOGOUT_REDIRECT_URI', env('APP_URL')),
];
