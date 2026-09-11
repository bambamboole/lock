<?php

declare(strict_types=1);

/**
 * laravel-oidc 0.27 lets a `client_credentials` token through the `auth:oidc`
 * guard, where it resolves to a ClientPrincipal instead of a user. The
 * principal carries no realm, no roles and no `can()`, so reaching for either
 * on it is a fatal error rather than a denial.
 */

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Auth\Support\RequestUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('a machine caller passes the token guard but is not mistaken for a person', function () {
    Route::middleware('auth:oidc')->get('/api/test/principal', fn (Request $request) => response()->json([
        'principal' => $request->user()?->getAuthIdentifier(),
        'person' => RequestUser::of($request)?->getAuthIdentifier(),
    ]));

    $client = $this->createOidcMachineClient();
    $user = User::factory()->for(Realm::master())->create();

    $this->getJson('/api/test/principal', ['Authorization' => 'Bearer '.$this->issueClientToken($client)])
        ->assertOk()
        ->assertJson(['principal' => $client->client_id, 'person' => null]);

    auth()->forgetGuards();

    $this->getJson('/api/test/principal', ['Authorization' => 'Bearer '.$this->issueTokenFor($user)])
        ->assertOk()
        ->assertJson(['principal' => $user->id, 'person' => $user->id]);
});

test('the realm middleware leaves the context empty for a client principal instead of failing', function () {
    Route::middleware('web')->get('/test/realm-context', fn () => response()->json(['realm' => Realm::currentId()]));

    $this->actingAsOidcClient($this->createOidcMachineClient());

    $this->getJson('/test/realm-context')->assertOk()->assertJson(['realm' => null]);
});

test('the realm middleware still derives the realm from a signed-in person', function () {
    Route::middleware('web')->get('/test/realm-context', fn () => response()->json(['realm' => Realm::currentId()]));

    $user = User::factory()->for(Realm::master())->create();

    $this->actingAs($user)->getJson('/test/realm-context')->assertOk()->assertJson(['realm' => $user->realm_id]);
});
