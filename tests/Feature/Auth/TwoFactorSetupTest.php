<?php

declare(strict_types=1);

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\TotpFactorProvider;
use Bambamboole\LaravelOidc\Ui\Forms\TwoFactorSetupForm;
use PragmaRX\Google2FA\Google2FA;

use function Tests\Helpers\assumeNotNull;

/**
 * The setup wizard is one form: step one picks an enrollment option, step two is
 * prepared by Lattice's resolve sub-request, and Finish confirms. These tests
 * drive both halves through the form endpoint the browser uses.
 *
 * @return array<string, mixed>
 */
function resolveSetup(mixed $test, User $user, string $option): array
{
    return $test->actingAs($user)
        ->submitForm(TwoFactorSetupForm::class, ['_sub' => 'resolve', 'option' => $option])
        ->assertOk()
        ->json('fields.setup.props');
}

it('begins the chosen enrollment and hands back its setup payload', function () {
    $user = User::factory()->create();

    $props = resolveSetup($this, $user, 'totp');

    expect($props['kind'])->toBe('code')
        ->and($props['secret'])->toBeString()
        ->and($props['qrSvg'])->toContain('<svg')
        ->and($user->totpFactors()->whereNull('confirmed_at')->count())->toBe(1);
});

it('asks the browser for the attachment the chosen option names', function () {
    $user = User::factory()->create();

    expect(resolveSetup($this, $user, 'passkey')['webauthnOptions']['authenticatorSelection']['authenticatorAttachment'])
        ->toBe('platform')
        ->and(resolveSetup($this, $user, 'security_key')['webauthnOptions']['authenticatorSelection']['authenticatorAttachment'])
        ->toBe('cross-platform');
});

it('reuses the pending enrollment while the option stays the same', function () {
    $user = User::factory()->create();

    $first = resolveSetup($this, $user, 'totp');
    $second = resolveSetup($this, $user, 'totp');

    expect($second['secret'])->toBe($first['secret'])
        ->and($user->totpFactors()->count())->toBe(1);
});

it('confirms the factor and issues recovery codes once', function () {
    $user = User::factory()->create();

    resolveSetup($this, $user, 'totp');
    $factor = app(TotpFactorProvider::class)->latestPendingFactor($user);
    assumeNotNull($factor, 'Resolving the setup step should have begun a TOTP enrollment.');

    $this->actingAs($user)->submitForm(TwoFactorSetupForm::class, [
        'option' => 'totp',
        'setup' => app(Google2FA::class)->getCurrentOtp($factor->secret),
    ])->assertRedirect();

    expect($user->totpFactors()->whereNotNull('confirmed_at')->count())->toBe(1)
        ->and($user->recoveryCodes()->count())->toBe(8);
});

it('rejects a confirmation that does not prove the setup', function () {
    $user = User::factory()->create();

    resolveSetup($this, $user, 'totp');

    $this->actingAs($user)->submitForm(TwoFactorSetupForm::class, [
        'option' => 'totp',
        'setup' => '000000',
    ])->assertInvalid(['setup']);

    expect($user->totpFactors()->whereNotNull('confirmed_at')->count())->toBe(0);
});

it('requires a known enrollment option', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(TwoFactorSetupForm::class, ['option' => 'sms', 'setup' => '000000'])
        ->assertInvalid(['option']);
});
