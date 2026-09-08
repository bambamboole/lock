<?php
declare(strict_types=1);

use App\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\assumeNotNull;

test('the dashboard landing redirects to the current tenant dashboard', function () {
    $user = User::factory()->create();
    $personalTenant = $user->personalTenant();
    assumeNotNull($personalTenant);

    $this->actingAs($user)->get(route('dashboard.home'))
        ->assertRedirect(route('dashboard', ['current_tenant' => $personalTenant->slug]));
});

test('users can logout', function () {
    $user = User::factory()->create();
    $idToken = 'self-sso-id-token';

    Http::fake([
        config('app.url').'/.well-known/openid-configuration' => Http::response([
            'issuer' => config('app.url'),
            'authorization_endpoint' => route('oidc.authorize'),
            'token_endpoint' => route('oidc.token'),
            'jwks_uri' => route('oidc.jwks'),
            'end_session_endpoint' => route('oidc.logout'),
        ]),
    ]);

    $response = $this->actingAs($user, 'web')
        ->actingAs($user, 'identity')
        ->withSession(['oidc-client.tokens.id_token' => $idToken])
        ->post(route('logout'))
        ->assertRedirect();

    Auth::forgetGuards();

    $this->assertGuest('web');
    $this->assertGuest('identity');
    expect($response->headers->get('Location'))
        ->toContain('/oauth/logout')
        ->toContain('id_token_hint=');
});

test('provider discovery failure still persists both local session logouts', function () {
    $user = User::factory()->create();

    config()->set('oidc-client.issuer', 'https://unavailable.example.com');

    Http::fake([
        'https://unavailable.example.com/.well-known/openid-configuration' => Http::response([], 503),
    ]);

    $this->actingAs($user, 'web')
        ->actingAs($user, 'identity')
        ->withSession(['oidc-client.tokens.id_token' => 'self-sso-id-token'])
        ->post(route('logout'))
        ->assertRedirect('/');

    Auth::forgetGuards();

    $this->get('/dashboard')->assertRedirect(route('login'));

    Auth::forgetGuards();

    $this->assertGuest('web');
    $this->assertGuest('identity');
});
