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

use function Tests\Helpers\realmRoute;
use function Tests\Helpers\runningTestCase;

/**
 * @return TestResponse<Response>
 */
function signInToRealm(User $user): TestResponse
{
    auth()->forgetGuards();
    runningTestCase()->flushSession();

    return runningTestCase()->post(
        realmRoute($user->realm, 'identity.login.store'),
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

    signInToRealm($user)->assertNotFound();
    $this->get(realmRoute($realm, 'identity.register'))->assertNotFound();

    $this->assertGuest('identity');

    signInToRealm(User::factory()->for(Realm::master())->create())->assertRedirect(realmRoute('admin', 'account'));
});

it('sends a user without a second factor to enrollment when the realm always requires one', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $user = User::factory()->for($realm)->create();

    signInToRealm($user)->assertRedirect(realmRoute($realm, 'account'));

    app(UpdateRealmConfiguration::class)->handle($realm, ['mfa_requirement' => 'always']);

    signInToRealm($user)->assertRedirect(realmRoute($realm, 'identity.two-factor.setup'));
});

it('holds an unverified address at the verification prompt when the realm requires verification', function () {
    $realm = Realm::factory()->create(['slug' => 'partners']);
    $user = User::factory()->for($realm)->unverified()->create();

    signInToRealm($user)->assertRedirect(realmRoute($realm, 'account'));

    app(UpdateRealmConfiguration::class)->handle($realm, ['email_verification_required' => true]);

    signInToRealm($user)->assertRedirect(realmRoute($realm, 'identity.verification.notice'));
});

it('falls back to the configured defaults for a realm with no stored authentication settings', function () {
    $realm = Realm::factory()->create(['slug' => 'legacy']);

    foreach (['login_methods', 'mfa_requirement', 'email_verification_required'] as $key) {
        $realm->settings->forget('realm.'.$key);
    }

    expect($realm->refresh()->authentication())->toEqual(AuthenticationSettings::fromConfig())
        ->and(RealmConfiguration::defaults()['login_methods'])->toBe(['password', 'passkey', 'social']);

    signInToRealm(User::factory()->for($realm)->create())->assertRedirect(realmRoute($realm, 'account'));
});
