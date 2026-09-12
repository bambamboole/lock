<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Api\ManagementApi;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;
use function Tests\Helpers\latticeSchema;
use function Tests\Helpers\playgroundTokenRequest;
use function Tests\Helpers\provisionManagementApi;
use function Tests\Helpers\realmUrl;
use function Tests\Helpers\selfSsoProviderFake;

beforeEach(function () {
    provisionManagementApi();
});

it('seals one playground reference per operation scope set of the document', function () {
    $response = $this->actingAs(globalAdmin())->get(route('admin.api'))->assertOk();
    $reference = latticeSchema($response)->firstOfTypeOrFail('api-reference');

    $scopeSets = array_map(fn (array $access): array => $access['scopes'], (array) $reference->prop('remoteTokens'));

    expect($scopeSets)->toEqualCanonicalizing([[ApiScope::RealmsRead->value], [ApiScope::RealmsWrite->value]]);
});

it('mints a scoped token on execute that the API accepts', function () {
    selfSsoProviderFake();

    $request = playgroundTokenRequest(ApiScope::RealmsRead);

    $token = $this->actingAs(globalAdmin())
        ->withHeader('X-Lattice-Ref', $request['ref'])
        ->postJson($request['endpoint'], $request['payload'])
        ->assertOk()
        ->json();

    expect($token['audience'])->toBe(app(ManagementApi::class)->audience())
        ->and($token['scopes'])->toBe([ApiScope::RealmsRead->value])
        ->and($token['expiresIn'])->toBeLessThanOrEqual(300);

    auth()->forgetGuards();

    $this->withToken($token['accessToken'])
        ->getJson(realmUrl(Realm::master(), '/api/v1/realms'))
        ->assertOk()
        ->assertJsonPath('data.0.slug', Realm::master()->slug);
});

it('refuses to mint a token for an admin who cannot write realms', function () {
    $request = playgroundTokenRequest(ApiScope::RealmsWrite);

    $this->actingAs(globalAdminWith(ApiScope::RealmsRead))
        ->withHeader('X-Lattice-Ref', $request['ref'])
        ->postJson($request['endpoint'], $request['payload'])
        ->assertForbidden();
});

it('refuses a scope set no sealed reference covers', function () {
    $request = playgroundTokenRequest(ApiScope::RealmsRead);
    $request['payload']['scopes'] = [ApiScope::RealmsWrite->value];

    $this->actingAs(globalAdmin())
        ->withHeader('X-Lattice-Ref', $request['ref'])
        ->postJson($request['endpoint'], $request['payload'])
        ->assertForbidden();
});

it('closes the page to an admin without realms:read', function () {
    $this->actingAs(globalAdminWith(ApiScope::ActivityRead))->get(route('admin.api'))->assertForbidden();
});
