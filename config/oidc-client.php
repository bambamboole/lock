<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('OIDC_RP_ENABLED', true),

    'issuer' => env('OIDC_RP_ISSUER', rtrim((string) env('APP_URL'), '/')),

    // The console is its own relying party, so the first-party credentials
    // configure both sides.
    'client_id' => env('OIDC_RP_CLIENT_ID') ?: env('OIDC_FIRST_PARTY_CLIENT'),

    'client_secret' => env('OIDC_RP_CLIENT_SECRET') ?: env('OIDC_FIRST_PARTY_SECRET'),

    // The package has no default: RelyingParty sends this string verbatim,
    // so an empty value breaks the authorize request.
    'redirect_uri' => env('OIDC_RP_REDIRECT_URI', env('APP_URL').'/login/callback'),

    'redirect_after_login' => env('OIDC_RP_HOME', '/'),

    'post_logout_redirect_uri' => env('OIDC_RP_POST_LOGOUT_REDIRECT_URI', env('APP_URL')),
];
