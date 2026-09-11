<?php
declare(strict_types=1);

use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;

it('redirects guests to login', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Test RP', ['https://rp.test/callback']);
    $verifier = str_repeat('a', 64);

    $this->get(route('oidc.authorize', [
        'client_id' => $client->client_id,
        'redirect_uri' => 'https://rp.test/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertRedirect(route('identity.login'));
});
