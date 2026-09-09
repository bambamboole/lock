<?php
declare(strict_types=1);

/**
 * The isolation laravel-oidc cannot provide: it does not own the user model, so
 * the realm constraint on credential lookup and on reset tokens is the
 * application's to enforce.
 */

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->acme = Realm::factory()->create(['slug' => 'acme']);
    $this->globex = Realm::factory()->create(['slug' => 'globex']);

    // One address, two unrelated people — what unique(realm_id, email) allows.
    $this->inAcme = User::factory()->for($this->acme)->create(['email' => 'chris@example.com']);
    $this->inGlobex = User::factory()->for($this->globex)->create(['email' => 'chris@example.com']);
});

it('signs the address in as the user of the realm it was offered to', function () {
    $signedInWithin = fn (Realm $realm): ?string => $realm->runAsCurrent(function (): ?string {
        auth()->forgetGuards();
        auth()->guard('identity')->attempt(['email' => 'chris@example.com', 'password' => 'password']);

        $user = auth()->guard('identity')->user();

        return $user instanceof User ? $user->id : null;
    });

    expect($signedInWithin($this->acme))->toBe($this->inAcme->id)
        ->and($signedInWithin($this->globex))->toBe($this->inGlobex->id);
});

it('does not resolve a session identifier from another realm', function () {
    $authenticated = $this->globex->runAsCurrent(
        fn (): bool => auth()->guard('identity')->onceUsingId($this->inAcme->id) !== false,
    );

    expect($authenticated)->toBeFalse();
});

it('does not accept a reset token minted for the same address in another realm', function () {
    $token = Password::broker()->createToken($this->inAcme);

    expect(Password::broker()->tokenExists($this->inAcme, $token))->toBeTrue()
        ->and(Password::broker()->tokenExists($this->inGlobex, $token))->toBeFalse();
});

it('keeps one realm reset request from discarding the other realm pending token', function () {
    $acmeToken = Password::broker()->createToken($this->inAcme);
    Password::broker()->createToken($this->inGlobex);

    expect(Password::broker()->tokenExists($this->inAcme, $acmeToken))->toBeTrue();
});
