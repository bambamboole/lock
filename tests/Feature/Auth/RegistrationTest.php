<?php
declare(strict_types=1);

use App\Auth\Models\User;

test('new users can register', function () {
    $response = $this->post(route('identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertGuest('web');
    expect($user->name)->toBe('Test User')
        ->and($user->realm->slug)->toBe(config('realm.default_slug'));
    $response->assertRedirect('/login');
});
