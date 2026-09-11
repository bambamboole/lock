<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\UpdateRealmConfiguration;
use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmConfiguration;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\AuthenticationSettings;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\LoginMethod;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\MfaRequirement;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

use function Tests\Helpers\runningTestCase;

/**
 * @return TestResponse<Response>
 */
function signIn(User $user): TestResponse
{
    auth()->forgetGuards();
    runningTestCase()->flushSession();

    return runningTestCase()->post(
        route('identity.login.store', ['realm' => $user->realm->slug]),
        ['email' => $user->email, 'password' => 'password'],
    );
}

it('reads the authentication settings of each realm from its own configuration', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);

    app(UpdateRealmConfiguration::class)->handle($realm, [
        'login_methods' => ['passkey'],
        'mfa_requirement' => 'always',
        'email_verification_required' => true,
    ]);

    $authentication = $realm->refresh()->authentication();

    expect($authentication->methods)->toBe([LoginMethod::Passkey])
        ->and($authentication->mfa)->toBe(MfaRequirement::Always)
        ->and($authentication->emailVerificationRequired)->toBeTrue()
        ->and(Realm::master()->authentication())->toEqual(AuthenticationSettings::fromConfig());
});

it('returns 404 on the password routes of a realm that does not accept the method', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $user = User::factory()->for($realm)->create();
    app(UpdateRealmConfiguration::class)->handle($realm, ['login_methods' => ['passkey']]);

    signIn($user)->assertNotFound();
    $this->get(route('identity.register', ['realm' => $realm->slug]))->assertNotFound();

    $this->assertGuest('identity');

    signIn(User::factory()->for(Realm::master())->create())->assertRedirect(route('account', ['realm' => 'admin']));
});

it('sends a user without a second factor to enrollment when the realm always requires one', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $user = User::factory()->for($realm)->create();

    signIn($user)->assertRedirect(route('account', ['realm' => $realm->slug]));

    app(UpdateRealmConfiguration::class)->handle($realm, ['mfa_requirement' => 'always']);

    signIn($user)->assertRedirect(route('identity.two-factor.setup', ['realm' => $realm->slug]));
});

it('holds an unverified address at the verification prompt when the realm requires verification', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $user = User::factory()->for($realm)->unverified()->create();

    signIn($user)->assertRedirect(route('account', ['realm' => $realm->slug]));

    app(UpdateRealmConfiguration::class)->handle($realm, ['email_verification_required' => true]);

    signIn($user)->assertRedirect(route('identity.verification.notice', ['realm' => $realm->slug]));
});

it('falls back to the configured defaults for a realm with no stored authentication settings', function () {
    $realm = Realm::factory()->create(['slug' => 'legacy']);

    foreach (['login_methods', 'mfa_requirement', 'email_verification_required'] as $key) {
        $realm->settings->forget('realm.'.$key);
    }

    expect($realm->refresh()->authentication())->toEqual(AuthenticationSettings::fromConfig())
        ->and(RealmConfiguration::defaults()['login_methods'])->toBe(['password', 'passkey', 'social']);

    signIn(User::factory()->for($realm)->create())->assertRedirect(route('account', ['realm' => $realm->slug]));
});
