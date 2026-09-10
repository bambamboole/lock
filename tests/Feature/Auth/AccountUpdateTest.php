<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Forms\EmailForm;
use App\Auth\Ui\Forms\NameForm;
use App\Auth\Ui\Forms\PasswordForm;
use Illuminate\Support\Facades\Hash;

test('users can update their name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->submitForm(NameForm::class, ['name' => 'New Name'])
        ->assertRedirect(route('account', ['realm' => $user->realm->slug]));

    expect($user->refresh()->name)->toBe('New Name');
});

test('users can update their email', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->submitForm(EmailForm::class, ['email' => 'new@example.com'])
        ->assertRedirect(route('account', ['realm' => $user->realm->slug]));

    expect($user->refresh()->email)->toBe('new@example.com');
});

test('changing the email resets the verification status', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->submitForm(EmailForm::class, ['email' => 'new@example.com'])
        ->assertRedirect();

    expect($user->refresh()->email_verified_at)->toBeNull();
});

test('users can update their password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(PasswordForm::class, [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Hash::check('new-secure-password', $user->refresh()->password))->toBeTrue();
});

test('a password change enforces the current password, the policy, and the confirmation', function (array $overrides, string $errorField) {
    $user = User::factory()->create();
    $originalHash = $user->password;

    $this->actingAs($user)
        ->submitForm(PasswordForm::class, [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            ...$overrides,
        ])
        ->assertSessionHasErrors($errorField);

    expect($user->refresh()->password)->toBe($originalHash);
})->with([
    'wrong current password' => [['current_password' => 'not-the-password'], 'current_password'],
    'weak new password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'mismatched confirmation' => [['password_confirmation' => 'different-password'], 'password'],
]);

test('updating with an unchanged email keeps the verification status', function () {
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->submitForm(EmailForm::class, ['email' => 'same@example.com'])
        ->assertRedirect();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
