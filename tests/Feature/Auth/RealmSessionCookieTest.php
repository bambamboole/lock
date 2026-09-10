<?php

declare(strict_types=1);

use App\Realms\Actions\UpdateRealmConfiguration;
use App\Realms\Models\Realm;

it('isolates provider session cookies from the app and other realms', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    app(UpdateRealmConfiguration::class)->handle($realm, ['session_cookie_name' => 'partners-identity']);
    $cookie = (string) config('session.cookie');

    $this->get('/login')->assertCookie($cookie);

    $admin = $this->get('/realms/admin/auth/login')->assertOk();
    $admin->assertCookie($cookie.'-oidc-admin')->assertCookieExpired($cookie);
    expect($admin->getCookie($cookie, decrypt: false)?->getPath())->toBe('/realms/admin')
        ->and($admin->getCookie($cookie.'-oidc-admin')?->getPath())->toBe('/realms/admin');

    $partner = $this->get('/realms/partners/auth/login')->assertOk();
    $partner->assertCookie('partners-identity')->assertCookieMissing($cookie.'-oidc-admin');
    expect($partner->getCookie('partners-identity')?->getPath())->toBe('/realms/partners');

    app(UpdateRealmConfiguration::class)->handle($realm, ['session_cookie_name' => '']);
    $this->get('/realms/partners/auth/login')->assertOk()->assertCookie($cookie.'-oidc-partners');
});
