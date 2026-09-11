<?php
declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmRoute;

test('the root path sends a master realm user to their account and an admin to the realms console', function () {
    (new GlobalRoleSeeder)->run();

    $this->get('/')->assertRedirect(route('login'));
    $this->actingAs(User::factory()->for(Realm::master())->create())->get('/')->assertRedirect(realmRoute(Realm::master(), 'account'));
    $this->actingAs(globalAdmin(GlobalRoleSeeder::SUPPORT))->get('/')->assertRedirect(route('admin.realms'));
});

test('users can logout', function () {
    $user = User::factory()->create();
    $idToken = 'self-sso-id-token';

    $issuer = app(IssuerResolver::class)->url();
    config()->set('oidc-client.issuer', $issuer);

    Http::fake([
        $issuer.'/.well-known/openid-configuration' => Http::response([
            'issuer' => $issuer,
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

    $this->get('/')->assertRedirect(route('login'));

    Auth::forgetGuards();

    $this->assertGuest('web');
    $this->assertGuest('identity');
});
