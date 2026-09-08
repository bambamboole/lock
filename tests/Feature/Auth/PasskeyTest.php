<?php

declare(strict_types=1);

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Ui\Actions\RevokeFactorAction;

use function Tests\Helpers\createPasskey;

test('users can revoke their own passkey through the lattice action', function () {
    $user = User::factory()->create();
    $passkey = createPasskey($user);

    $this->actingAs($user)
        ->callAction(RevokeFactorAction::class, context: ['provider' => 'webauthn', 'enrollment' => (string) $passkey->id])
        ->assertOk();

    expect($user->passkeys()->whereKey($passkey->id)->exists())->toBeFalse();
});

test('users cannot revoke another users passkey', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $passkey = createPasskey($other);

    $this->actingAs($user)
        ->callDeniedAction(RevokeFactorAction::class, context: ['provider' => 'webauthn', 'enrollment' => (string) $passkey->id])
        ->assertForbidden();

    expect($other->passkeys()->whereKey($passkey->id)->exists())->toBeTrue();
});
