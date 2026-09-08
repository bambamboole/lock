<?php
declare(strict_types=1);

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\FactorRegistry;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\RecoveryCodeProvider;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\TotpFactorProvider;
use Bambamboole\LaravelOidc\Ui\Actions\RevokeFactorAction;

use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\createPasskey;

/**
 * How this app composes the security card. Enrolling a factor is covered by
 * TwoFactorSetupTest and the list itself by TwoFactorMethodsTest; what is left
 * here is the page's own gate, the promise that codes never appear on it, and
 * the "remove the last method" contract the card relies on instead of a switch.
 */
test('security settings stay hidden until the password is confirmed', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('user.security.confirm.action'))
        ->assertDontSee(__('user.security.two-factor.add-method'));
});

test('confirming the password reveals the two-factor section', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('user.security.two-factor.add-method'))
        ->assertSee(__('user.security.two-factor.status.enabled'));
});

test('recovery codes never reach the profile page', function () {
    $user = User::factory()->withTwoFactor()->create();
    $code = app(RecoveryCodeProvider::class)->codes($user)[0];

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee($code);
});

test('a passkey alone counts as a second factor', function () {
    $user = User::factory()->create();
    createPasskey($user, 'MacBook Pro');

    expect(app(FactorRegistry::class)->configuredChallengeableEnrollments($user))->toHaveCount(1);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('user.security.two-factor.status.enabled'));
});

test('removing the last method takes the recovery codes with it', function () {
    $user = User::factory()->withTwoFactor()->create();
    $factor = app(TotpFactorProvider::class)->latestFactor($user);
    assumeNotNull($factor, 'The withTwoFactor state should have enrolled a TOTP factor.');

    expect($user->recoveryCodes()->count())->toBe(8);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->callAction(RevokeFactorAction::class, context: [
            'provider' => 'totp',
            'enrollment' => (string) $factor->getKey(),
        ])->assertOk();

    expect($user->totpFactors()->exists())->toBeFalse()
        ->and($user->recoveryCodes()->exists())->toBeFalse()
        ->and(app(FactorRegistry::class)->configuredChallengeableEnrollments($user))->toBeEmpty();
});
