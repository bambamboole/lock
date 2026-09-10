<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Testing\TestResponse;

use function Tests\Helpers\selfSsoProviderFake;

/*
 * The claim-by-claim validation matrix lives in the package's own
 * IdTokenValidator tests; these tests cover this app's integration:
 * the same app acting as provider and relying party.
 */

/**
 * The Location of a response the test already asserted redirects.
 *
 * @param  TestResponse<Response>  $response
 */
function redirectTarget(TestResponse $response): string
{
    $location = $response->headers->get('Location');

    if ($location === null) {
        throw new RuntimeException('The response carries no Location header.');
    }

    return $location;
}

it('establishes the web session only after the self-sso callback', function () {
    Exceptions::fake();

    $user = User::factory()->for(Realm::master())->create();
    $fake = selfSsoProviderFake();

    $this->get('/')->assertRedirect(route('login'));
    $this->assertGuest('identity');
    $this->assertGuest('web');

    $login = $this->get(route('login'))->assertRedirect();
    $fake->assertRedirectedToProvider($login);

    // The real login redirect seeded state/nonce/verifier; mint the id_token
    // against the live nonce so the callback validates end to end.
    $nonce = session('oidc-client.nonce');
    expect($nonce)->toBeString()->not->toBeEmpty();
    $fake->loginAs($user, ['nonce' => $nonce]);

    $this->get(redirectTarget($login))->assertRedirect(route('identity.login'));
    $this->assertGuest('identity');
    $this->assertGuest('web');

    $identityLogin = $this->post(route('identity.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertGuest('web');

    $oidcSession = OidcSession::query()->where('user_id', (string) $user->getAuthIdentifier())->sole();
    $rootSessionToken = session('oidc.session_token')['jwt'] ?? null;

    expect($oidcSession->sid)->not->toBeEmpty()
        ->and($rootSessionToken)->toBeString()->not->toBeEmpty()
        ->and(session('oidc.sid'))->toBe($oidcSession->sid);

    $authorization = $this->get(redirectTarget($identityLogin))->assertRedirect();
    expect(redirectTarget($authorization))->toStartWith(config('app.url').'/login/callback?');
    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertGuest('web');

    $this->get(redirectTarget($authorization))->assertRedirect('/');
    $this->assertAuthenticatedAs($user, 'identity');
    $this->assertAuthenticatedAs($user, 'web');
    $fake->assertCodeExchanged();
    Exceptions::assertNothingReported();
});

it('rejects an id token minted for a different nonce', function () {
    $user = User::factory()->for(Realm::master())->create();
    $fake = selfSsoProviderFake();

    $this->withSession($fake->callbackContext())
        ->get($fake->loginAs($user, ['nonce' => 'wrong-nonce']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('oidc');

    $this->assertGuest('web');
});

it('rejects a failed token exchange without creating a web session', function () {
    $fake = selfSsoProviderFake()->failTokenExchange();

    $this->withSession($fake->callbackContext())
        ->get($fake->callbackUrl())
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('oidc');

    $this->assertGuest('web');
    $fake->assertCodeExchanged();
});

it('rejects an id token for an unknown subject', function () {
    $fake = selfSsoProviderFake();

    // The fake's default subject does not exist in the users table.
    $this->withSession($fake->callbackContext())
        ->get($fake->callbackUrl())
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('oidc');

    $this->assertGuest('web');
});
