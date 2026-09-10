<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Client\Facades\OidcClient;
use Bambamboole\LaravelOidc\Client\Testing\OidcClientFake;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;
use Tests\TestCase;

/**
 * Points the relying-party configuration at a client served by this same app,
 * so a login round-trip stays entirely in-process.
 */
function selfSsoProviderFake(?Client $client = null): OidcClientFake
{
    $client ??= app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'First-party app',
        [config('app.url').'/login/callback'],
    );

    config()->set([
        'oidc-client.issuer' => app(IssuerResolver::class)->url(),
        'oidc-client.client_id' => $client->client_id,
        'oidc-client.client_secret' => $client->plainSecret,
    ]);

    $realm = Realm::master();
    $realm->settings->set('realm.first_party_client_id', $client->client_id);
    $realm->settings->set('realm.first_party_trusted', true);

    return OidcClient::fake();
}

/**
 * Walks the full authorization-code + PKCE flow against $test->client and
 * returns the issued access token.
 */
function loginAccessToken(TestCase $test): string
{
    $verifier = str_repeat('v', 64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    $view = $test->actingAs($test->user, 'identity')
        ->withSession(['oidc.auth_time' => time()])
        ->get(route('oidc.authorize', [
            'client_id' => $test->client->client_id,
            'redirect_uri' => 'https://rp.test/callback',
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 's',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]))
        ->assertOk();

    $approve = $test->post(route('oidc.approve'), ['auth_token' => $view->json('authToken')])
        ->assertRedirect();

    parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $params);

    return $test->post(route('oidc.token'), [
        'grant_type' => 'authorization_code',
        'client_id' => $test->client->client_id,
        'client_secret' => $test->client->plainSecret,
        'redirect_uri' => 'https://rp.test/callback',
        'code' => $params['code'],
        'code_verifier' => $verifier,
    ])->json('access_token');
}

/**
 * The payload of a signed JWT as the relying party reads it, without
 * validating the signature — the tests only inspect what was minted.
 *
 * @return array<string, mixed>
 */
function jwtClaims(mixed $jwt): array
{
    if (! is_string($jwt)) {
        throw new RuntimeException('Expected a JWT string.');
    }

    $segments = explode('.', $jwt);

    if (count($segments) !== 3) {
        throw new RuntimeException('The token is not a compact JWS.');
    }

    $payload = json_decode(base64_decode(strtr($segments[1], '-_', '+/'), true) ?: '', true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($payload)) {
        throw new RuntimeException('The JWT payload is not an object.');
    }

    return $payload;
}
