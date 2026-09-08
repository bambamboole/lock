<?php

declare(strict_types=1);

/**
 * OAuth 2.1 §4.1 + RFC 7636 (PKCE) + RFC 8693 (exchange) + RFC 9068 §4 (RS validation) + RFC 6750 §3.1
 */

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Http\Middleware\CheckAudience;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

use function Tests\Helpers\loginAccessToken;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    // The oidc guard only accepts an exchanged token whose `aud` it serves;
    // a resource server has to declare its own audiences.
    config(['oidc.resource.audiences' => ['https://api.internal/orders']]);
    Passport::authorizationView(fn (array $parameters) => response()->json(['authToken' => $parameters['authToken']]));

    $this->user = User::factory()->create();
    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('RP', ['https://rp.test/callback']);
    $this->client->forceFill([
        'grant_types' => [...$this->client->grant_types, 'urn:ietf:params:oauth:grant-type:token-exchange'],
        'allowed_exchange_audiences' => json_encode(['https://api.internal/orders']),
    ])->save();

    Route::middleware(['auth:oidc', CheckAudience::using('https://api.internal/orders')])
        ->get('/api/test/orders', fn (Request $request) => response()->json([
            'user' => $request->user()?->getAuthIdentifier(),
        ]));
});

it('exchanges a first-party login token and enforces the audience on a CheckAudience-only route', function () {
    $subject = loginAccessToken($this);

    $exchanged = $this->post(route('oidc.token'), [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'subject_token' => $subject,
        'subject_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
        'audience' => 'https://api.internal/orders',
        'scope' => 'openid',
    ])->assertOk()->json('access_token');

    $this->getJson('/api/test/orders', ['Authorization' => "Bearer $exchanged"])
        ->assertOk()
        ->assertJson(['user' => $this->user->id]);

    $this->getJson('/api/test/orders', ['Authorization' => "Bearer $subject"])
        ->assertForbidden();

    // The oidc guard caches its resolved user and only gets a refreshed request,
    // never a reset. Real requests each build a fresh container; a test reuses
    // one across calls, so the previous bearer token would still authenticate here.
    auth()->forgetGuards();

    $this->getJson('/api/test/orders')->assertUnauthorized();
});
