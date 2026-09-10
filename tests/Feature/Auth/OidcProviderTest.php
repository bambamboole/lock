<?php

declare(strict_types=1);

/**
 * OpenID Connect Discovery 1.0 §3; OpenID Connect Core 1.0 §5.3 (userinfo), §5.1 (standard claims)
 */

use App\Auth\Models\User;

it('serves discovery under the admin realm issuer', function () {
    $this->getJson(route('oidc.discovery'))->assertOk()
        ->assertJsonPath('issuer', config('app.url').'/realms/admin')
        ->assertJsonPath('token_endpoint', config('app.url').'/realms/admin/oauth/token');
});

it('returns app-specific claims from userinfo', function () {
    $user = User::factory()->create(['locale' => 'de', 'timezone' => 'Europe/Berlin']);
    $token = $this->issueTokenFor($user, scopes: ['openid', 'profile', 'email']);

    $this->withToken($token)->getJson(route('oidc.userinfo'))
        ->assertOk()
        ->assertJsonPath('sub', (string) $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('locale', 'de')
        ->assertJsonPath('zoneinfo', 'Europe/Berlin');
});
