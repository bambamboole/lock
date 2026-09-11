<?php

declare(strict_types=1);

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Credentials\RecoveryCodeProvider;
use Bambamboole\LaravelOidc\Ui\Tables\TwoFactorMethodsTable;

use function Tests\Helpers\assumeNotNull;

/**
 * There is no separate two-factor switch, so the list has to be complete.
 * Assertions stay on the projected payload because that is all the client
 * receives.
 *
 * @return array<int, array<string, mixed>>
 */
function methodRows(mixed $test, User $user): array
{
    return $test->actingAs($user)
        ->loadTable(TwoFactorMethodsTable::class)
        ->assertOk()
        ->json('data');
}

/**
 * @return array<string, mixed>
 */
function methodRow(mixed $test, User $user, string $label): array
{
    $row = collect(methodRows($test, $user))->firstWhere('label', $label);
    assumeNotNull($row, "The methods list has no row labelled [{$label}].");

    return $row;
}

function recoveryRowLabel(): string
{
    return __('oidc-ui::security.recovery-codes.heading');
}

it('names a confirmed factor and what it is good for', function () {
    $user = User::factory()->withTwoFactor()->create();

    $row = methodRow($this, $user, 'Authenticator app');

    expect($row['role'])->toBe(__('oidc-ui::security.role.second-factor-only'))
        ->and($row['description'])->toBe(__('oidc-ui::auth.two-factor.method.totp'))
        ->and($row['last_used_at_diff'])->toBe(__('oidc-ui::security.methods.never-used'))
        ->and($row['actions'])->toHaveCount(1);
});

it('adds a recovery-codes row reporting how many are left', function () {
    $user = User::factory()->withTwoFactor()->create();
    $codes = app(RecoveryCodeProvider::class);
    $codes->verify($user, $codes->beginChallenge($user, $codes->enrollments($user)[0]), [
        'recovery_code' => $codes->codes($user)[0],
    ]);

    $row = methodRow($this, $user, recoveryRowLabel());

    expect($row['description'])->toBe(__('oidc-ui::security.recovery-codes.remaining', [
        'remaining' => 7,
        'total' => 8,
    ]))
        ->and($row['role'])->toBe(__('oidc-ui::security.role.backup'))
        ->and($row['actions'])->toHaveCount(1);
});

it('leaves the recovery-codes row out while nothing it backs up is confirmed', function () {
    $user = User::factory()->create();
    app(RecoveryCodeProvider::class)->generate($user);

    expect(collect(methodRows($this, $user))->firstWhere('label', recoveryRowLabel()))->toBeNull();
});
