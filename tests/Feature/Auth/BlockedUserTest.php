<?php
declare(strict_types=1);

/**
 * Blocking is enforced in one seam — RealmUserProvider hides blocked users from
 * every lookup — plus a postLogin denial for the interactive logins that reach
 * the user another way. These tests hit each entry point from the outside.
 */

use App\Admin\Actions\BlockUser;
use App\Admin\Actions\UnblockUser;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\LoginEvent;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\PostLoginPipeline;
use Bambamboole\LaravelOidc\Server\Shared\Authentication\DeviceRecognizer;
use Illuminate\Http\Request;

function loginEventFor(User $user): LoginEvent
{
    return new LoginEvent(
        user: $user,
        client: null,
        scopes: [],
        requestedAcrValues: [],
        ip: null,
        userAgent: null,
        amr: ['swk'],
        authTime: null,
        recognizer: app(DeviceRecognizer::class),
        request: Request::create('/'),
    );
}

test('a blocked user cannot sign in with a valid password', function () {
    $user = User::factory()->for(Realm::master())->create();
    app(BlockUser::class)->handle($user);

    $this->post(route('identity.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertInvalid(['email']);

    $this->assertGuest('identity');
});

test('blocking a user drops the session they are already signed in with', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->post(route('identity.login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($user, 'identity');

    app(BlockUser::class)->handle($user);
    auth()->forgetGuards();

    $this->assertGuest('identity');
});

test('a bearer token of a blocked user stops authenticating', function () {
    $user = User::factory()->for(Realm::master())->create();
    $token = $this->issueTokenFor($user, scopes: ['openid', 'profile']);

    $this->withToken($token)->getJson(route('oidc.userinfo'))->assertOk();

    app(BlockUser::class)->handle($user);
    auth()->forgetGuards();

    $this->withToken($token)->getJson(route('oidc.userinfo'))->assertUnauthorized();
});

test('the post-login policy denies a blocked user resolved by a passkey or social login', function () {
    $user = User::factory()->for(Realm::master())->create();
    $pipeline = app(PostLoginPipeline::class);

    expect($pipeline->run(loginEventFor($user))->isDenied())->toBeFalse();

    app(BlockUser::class)->handle($user);

    $api = $pipeline->run(loginEventFor($user->refresh()));

    expect($api->isDenied())->toBeTrue()
        ->and($api->denyReason())->toBe('blocked');
});

test('unblocking restores the password login', function () {
    $user = User::factory()->for(Realm::master())->create();
    app(BlockUser::class)->handle($user);
    app(UnblockUser::class)->handle($user->refresh());

    $this->post(route('identity.login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();

    $this->assertAuthenticatedAs($user, 'identity');
});
