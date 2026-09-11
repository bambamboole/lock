<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\UpdateRealmConfiguration;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Authentication\PasswordResetTokens;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

use function Tests\Helpers\realmRoute;
use function Tests\Helpers\runningTestCase;

/**
 * @return TestResponse<Response>
 */
function registerIn(Realm $realm, string $password): TestResponse
{
    auth()->forgetGuards();

    return runningTestCase()->post(realmRoute($realm, 'identity.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);
}

it('enforces each realms own password policy at registration', function () {
    $strict = Realm::factory()->create(['slug' => 'strict']);
    $lenient = Realm::factory()->create(['slug' => 'lenient']);
    app(UpdateRealmConfiguration::class)->handle($strict, ['password_min_length' => 12, 'password_numbers' => true]);

    registerIn($strict, 'tencharspw')->assertSessionHasErrors('password');
    registerIn($strict, 'twelvechars12')->assertSessionHasNoErrors();
    registerIn($lenient, 'tencharspw')->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'test@example.com')->pluck('realm_id')->all())
        ->toEqualCanonicalizing([$strict->id, $lenient->id]);
});

it('enforces the realms password policy when a password is reset', function () {
    $realm = Realm::factory()->create(['slug' => 'strict']);
    app(UpdateRealmConfiguration::class)->handle($realm, ['password_min_length' => 12]);
    $user = User::factory()->for($realm)->create();
    $token = $realm->runAsCurrent(fn (): string => app(PasswordResetTokens::class)->create($user));

    $reset = fn (string $password) => $this->post(realmRoute($realm, 'identity.password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $reset('tencharspw')->assertSessionHasErrors('password');

    expect(Hash::check('tencharspw', $user->refresh()->password))->toBeFalse();

    $reset('twelvechars12')->assertSessionHasNoErrors();

    expect(Hash::check('twelvechars12', $user->refresh()->password))->toBeTrue();
});
