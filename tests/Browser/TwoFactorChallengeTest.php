<?php
declare(strict_types=1);

use App\Auth\Models\User;

it('hands a two-factor user off to the challenge route after login', function () {
    User::factory()->withTwoFactor()->create([
        'email' => 'grace@example.com',
        'password' => 'password',
    ]);

    $page = visit('/auth/login');

    $page->fill('email', 'grace@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/auth/two-factor-challenge')
        ->assertNoJavaScriptErrors();
});
