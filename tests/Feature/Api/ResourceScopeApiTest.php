<?php
declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Roles\Models\Role;
use App\Shared\Authorization\Enums\Scope;

use function Tests\Helpers\managementApiToken;
use function Tests\Helpers\realmUrl;

it('creates a scope, preserves omitted values, clears its description and deletes its role grants', function () {
    $resource = Resource::factory()->create();
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");
    $this->withToken(managementApiToken($this, Scope::ResourcesWrite));

    $response = $this->postJson($url, ['value' => 'orders:read', 'description' => 'Read orders'])
        ->assertCreated()->assertJsonPath('data.resource_id', $resource->id);
    $scope = ResourceScope::query()->where('id', $response->json('data.id'))->sole();
    expect($scope->value)->toBe('orders:read');

    auth()->forgetGuards();

    $this->patchJson($url.'/'.$scope->id, ['description' => null])->assertOk()
        ->assertJsonPath('data.description', null)->assertJsonPath('data.value', 'orders:read');
    expect($scope->refresh()->description)->toBeNull();

    $role = Role::factory()->for($resource->realm)->create();
    $role->scopes()->attach($scope);
    auth()->forgetGuards();

    $this->deleteJson($url.'/'.$scope->id)->assertNoContent();
    $this->assertModelMissing($scope);
    expect($role->scopes()->count())->toBe(0);
});

it('lists only the addressed resource scopes and shows a scope by UUID', function () {
    $resource = Resource::factory()->create();
    $scope = ResourceScope::factory()->for($resource)->create(['value' => 'read']);
    ResourceScope::factory()->for(Resource::factory()->for($resource->realm))->create(['value' => 'read']);
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");
    $this->withToken(managementApiToken($this, Scope::ResourcesRead));

    $this->getJson($url.'?filter[value]=read&sort=-value&per_page=1')->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $scope->id);
    auth()->forgetGuards();

    $this->getJson($url.'/'.$scope->id)->assertOk()->assertJsonPath('data.id', $scope->id);
});

it('returns 404 when any scope parent does not match', function (string $method, bool $wrongRealm) {
    $resource = Resource::factory()->create();
    $scope = ResourceScope::factory()->for($resource)->create();
    $other = Resource::factory()->for($wrongRealm ? Realm::factory()->create() : $resource->realm)->create();
    $realm = $wrongRealm ? $other->realm : $resource->realm;
    $resourceId = $wrongRealm ? $resource->id : $other->id;
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$realm->slug}/resources/{$resourceId}/scopes/{$scope->id}");

    $this->withToken(managementApiToken($this, Scope::ResourcesRead, Scope::ResourcesWrite))
        ->json($method, $url, ['description' => 'Changed'])->assertNotFound();

    expect($scope->refresh()->description)->not->toBe('Changed');
    $this->assertModelExists($scope);
})->with(['GET', 'PATCH', 'DELETE'])->with([true, false]);

it('returns 422 for duplicate scope values within a resource on create and update', function () {
    $resource = Resource::factory()->create();
    ResourceScope::factory()->for($resource)->create(['value' => 'read']);
    $scope = ResourceScope::factory()->for($resource)->create(['value' => 'write']);
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");
    $this->withToken(managementApiToken($this, Scope::ResourcesWrite));

    $this->postJson($url, ['value' => 'read'])->assertUnprocessable()->assertJsonValidationErrors('value');
    auth()->forgetGuards();

    $this->patchJson($url.'/'.$scope->id, ['value' => 'read'])->assertUnprocessable()->assertJsonValidationErrors('value');
    expect($scope->refresh()->value)->toBe('write')
        ->and($resource->scopes()->count())->toBe(2);
});

it('allows the same scope value in different resources and an unchanged value on update', function () {
    $resource = Resource::factory()->create();
    ResourceScope::factory()->create(['value' => 'read']);
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");
    $this->withToken(managementApiToken($this, Scope::ResourcesWrite));

    $response = $this->postJson($url, ['value' => 'read'])->assertCreated()->assertJsonPath('data.description', null);
    $scope = ResourceScope::query()->where('id', $response->json('data.id'))->sole();
    auth()->forgetGuards();

    $this->patchJson($url.'/'.$scope->id, ['value' => 'read'])->assertOk()->assertJsonPath('data.value', 'read');
    expect($scope->refresh()->value)->toBe('read');
});

it('returns 422 for malformed scope payloads', function (array $payload, string $field) {
    $resource = Resource::factory()->create();
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");

    $this->withToken(managementApiToken($this, Scope::ResourcesWrite))
        ->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($resource->scopes()->count())->toBe(0);
})->with([
    'missing value' => [[], 'value'],
    'space in value' => [['value' => 'orders read'], 'value'],
    'long value' => [['value' => str_repeat('a', 256)], 'value'],
    'long description' => [['value' => 'read', 'description' => str_repeat('a', 256)], 'description'],
    'unknown property' => [['value' => 'read', 'resource_id' => 'other'], 'resource_id'],
]);

it('returns 403 for changes to the protected management scopes', function (string $method) {
    $this->withToken(managementApiToken($this, Scope::ResourcesWrite));
    $resource = Realm::master()->realmResources()->where('identifier', 'api')->sole();
    $scope = $resource->scopes()->where('value', Scope::ResourcesRead->value)->sole();
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes".($method === 'POST' ? '' : '/'.$scope->id));

    $this->json($method, $url, ['value' => 'changed'])->assertForbidden();

    expect($scope->refresh()->value)->toBe(Scope::ResourcesRead->value)
        ->and($resource->scopes()->where('value', 'changed')->exists())->toBeFalse();
})->with(['POST', 'PATCH', 'DELETE']);

it('requires authentication and the resources write scope', function () {
    $resource = Resource::factory()->create();
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$resource->realm->slug}/resources/{$resource->id}/scopes");

    $this->getJson($url)->assertUnauthorized();
    $this->withToken(managementApiToken($this, Scope::ResourcesRead))->postJson($url, ['value' => 'read'])->assertForbidden();

    expect($resource->scopes()->count())->toBe(0);
});

it('returns 404 when listing or creating scopes through another realm', function (string $method) {
    $resource = Resource::factory()->create();
    $realm = Realm::factory()->create();
    $url = realmUrl(Realm::master(), "/api/v1/realms/{$realm->slug}/resources/{$resource->id}/scopes");

    $this->withToken(managementApiToken($this, Scope::ResourcesRead, Scope::ResourcesWrite))
        ->json($method, $url, ['value' => 'read'])->assertNotFound();

    expect($resource->scopes()->count())->toBe(0);
})->with(['GET', 'POST']);
