<?php
declare(strict_types=1);

/**
 * OAuth 2.1 §4.1.1–4.1.2 (authorization request/response); OpenID Connect Core 1.0 §3.1.2 (authorization endpoint / consent)
 */

use Laravel\Passport\ClientRepository;

it('redirects guests to login', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Test RP', ['https://rp.test/callback']);
    $verifier = str_repeat('a', 64);

    $this->get(route('oidc.authorize', [
        'client_id' => $client->id,
        'redirect_uri' => 'https://rp.test/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertRedirect(route('identity.login'));
});
