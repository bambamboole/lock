<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Support\DomainCheckToken;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmUrl;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->realm = Realm::factory()->create(['slug' => 'acme', 'domain' => 'auth.acme.test']);
});

it('serves a realm its sign-in and discovery from its own host and none of the console', function () {
    $this->get('http://auth.acme.test/auth/login')->assertOk();
    $this->getJson('http://auth.acme.test/.well-known/openid-configuration')
        ->assertOk()
        ->assertJsonPath('issuer', 'http://auth.acme.test');

    $this->actingAs(globalAdmin())->get('http://auth.acme.test/admin/realms')->assertNotFound();
    $this->get('http://auth.acme.test/login')->assertNotFound();
});

it('sends the root of a realm host to the realm account', function () {
    $this->get('http://auth.acme.test/')->assertRedirect('http://auth.acme.test/account');
});

it('answers a host no realm is served from with nothing but the health and domain checks', function () {
    $this->get('http://intruder.test/auth/login')->assertNotFound();
    $this->get('http://intruder.test/auth/forgot-password')->assertNotFound();

    $this->get('http://intruder.test/up')->assertOk();
    $this->get('http://intruder.test/.well-known/lock/domain-check')
        ->assertOk()
        ->assertContent(DomainCheckToken::for('intruder.test'));
});

it('names the realm host as issuer outside a request as well', function () {
    expect($this->realm->runAsCurrent(fn (): string => app(IssuerResolver::class)->url()))->toBe('http://auth.acme.test')
        ->and(Realm::master()->runAsCurrent(fn (): string => app(IssuerResolver::class)->url()))->toBe(config('app.url'));
});

it('points the mails a realm user gets from the console at their realm host', function () {
    Notification::fake();
    $user = User::factory()->for($this->realm)->unverified()->create();

    $this->actingAs(globalAdmin())->callAction(SendUserPasswordReset::class, [], ['user' => $user->id])->assertOk();
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, ResetPassword::class, fn (ResetPassword $notification): bool => str_starts_with(
        (string) $notification->toMail($user)->actionUrl,
        realmUrl($this->realm, '/auth/reset-password/'),
    ));
    Notification::assertSentTo($user, VerifyEmail::class, fn (VerifyEmail $notification): bool => str_starts_with(
        (string) $notification->toMail($user)->actionUrl,
        realmUrl($this->realm, '/auth/email/verify/'),
    ));
});
