<?php

declare(strict_types=1);

/**
 * A `client_credentials` token passes the `auth:oidc` guard as a
 * ClientPrincipal with no realm, roles or `can()`, so reaching for either is a
 * fatal error rather than a denial.
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

test('the realm middleware derives the realm from a person and leaves it empty for a client principal', function () {
    Route::middleware('web')->get('/test/realm-context', fn () => response()->json(['realm' => Realm::currentId()]));
    $user = User::factory()->for(Realm::master())->create();

    $this->actingAs($user)->getJson('/test/realm-context')->assertOk()->assertJson(['realm' => $user->realm_id]);

    auth()->forgetGuards();
    $this->actingAsOidcClient($this->createOidcMachineClient());

    $this->getJson('/test/realm-context')->assertOk()->assertJson(['realm' => null]);
});
