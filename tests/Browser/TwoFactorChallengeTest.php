<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;

it('hands a two-factor user off to the challenge route after login', function () {
    User::factory()->for(Realm::master())->withTwoFactor()->create([
        'email' => 'grace@example.com',
        'password' => 'password',
    ]);

    $page = visit('/realms/admin/auth/login');

    $page->fill('email', 'grace@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/realms/admin/auth/two-factor-challenge')
        ->assertNoJavaScriptErrors();
});
