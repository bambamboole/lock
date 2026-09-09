<?php

declare(strict_types=1);

/**
 * OpenID Connect Discovery 1.0 §3; OpenID Connect Core 1.0 §5.3 (userinfo), §5.1 (standard claims)
 */

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Facades\Oidc;

it('serves discovery with the realm scopes included', function () {
    $discovery = $this->getJson(route('oidc.discovery'))->assertOk();

    expect($discovery->json('scopes_supported'))->toContain('member:view');
});

it('returns app-specific claims from userinfo', function () {
    $user = User::factory()->create(['locale' => 'de', 'timezone' => 'Europe/Berlin']);
    Oidc::actingAs($user, ['openid', 'profile', 'email'], 'oidc');

    $this->getJson(route('oidc.userinfo'))
        ->assertOk()
        ->assertJsonPath('sub', (string) $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('locale', 'de')
        ->assertJsonPath('zoneinfo', 'Europe/Berlin');
});
