<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Consents\Views\ConsentPrompt;
use Bambamboole\LaravelOidc\Server\Consents\Views\ConsentView;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;
use Bambamboole\LaravelOidc\Server\Tokens\Http\Middleware\CheckAudience;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Tests\Helpers\loginAccessToken;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    Resource::factory()->for(Realm::master())->create(['identifier' => 'orders', 'name' => 'Orders']);
    $this->audience = app(IssuerResolver::class)->url().'/orders';
    app()->bind(ConsentView::class, fn () => new class implements ConsentView
    {
        public function respond(ConsentPrompt $prompt, Request $request): JsonResponse
        {
            return response()->json(['authToken' => $prompt->authToken]);
        }
    });

    $this->user = User::factory()->for(Realm::master())->create();
    $this->client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('RP', ['https://rp.test/callback']);
    $this->client->forceFill([
        'grant_types' => [...$this->client->grant_types, 'urn:ietf:params:oauth:grant-type:token-exchange'],
        'allowed_exchange_audiences' => [$this->audience],
    ])->save();

    Route::middleware(['auth:oidc', CheckAudience::using($this->audience)])
        ->get('/api/test/orders', fn (Request $request) => response()->json([
            'user' => $request->user()?->getAuthIdentifier(),
        ]));
});

it('exchanges a starter-kit login token and enforces the audience on a CheckAudience-only route', function () {
    $subject = loginAccessToken($this);

    $exchanged = $this->post(route('oidc.token'), [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
        'client_id' => $this->client->client_id,
        'client_secret' => $this->client->plainSecret,
        'subject_token' => $subject,
        'subject_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
        'audience' => $this->audience,
        'scope' => 'openid',
    ])->assertOk()->json('access_token');

    $this->getJson('/api/test/orders', ['Authorization' => "Bearer $exchanged"])
        ->assertOk()
        ->assertJson(['user' => $this->user->id]);

    $otherAudience = $this->issueTokenFor($this->user, $this->client, audience: [app(IssuerResolver::class)->url()]);
    auth()->forgetGuards();

    $rejected = $this->getJson('/api/test/orders', ['Authorization' => "Bearer $otherAudience"])
        ->assertUnauthorized();

    expect($rejected->headers->get('WWW-Authenticate'))->toContain('error="invalid_token"');

    // The oidc guard caches its resolved user across the calls of one test, so
    // the previous bearer token would still authenticate.
    auth()->forgetGuards();

    $this->getJson('/api/test/orders')->assertUnauthorized();
});
