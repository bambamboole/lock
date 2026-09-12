<?php

declare(strict_types=1);

use App\Audit\Models\AdminEvent;
use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Resources\Contracts\CreatesResource;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use App\Shared\Resources\Contracts\DeletesResource;
use App\Shared\Resources\Contracts\DeletesResourceScope;
use App\Shared\Resources\Contracts\UpdatesResource;
use App\Shared\Resources\Contracts\UpdatesResourceScope;
use App\Shared\Resources\Enums\ResourceAdminEvent;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
});

test('a realm serves its resources and their scopes to the oidc server', function () {
    $api = app(CreatesResource::class)->handle($this->realm, 'api', 'Orders API');
    app(CreatesResourceScope::class)->handle($api, 'orders.read', 'Read orders');
    app(CreatesResourceScope::class)->handle($api, 'orders.write', null);

    $external = app(CreatesResource::class)->handle($this->realm, 'https://billing.test', 'Billing');
    app(CreatesResourceScope::class)->handle($external, 'invoices.read', null);

    expect($this->realm->resources()->resources)->toBe([
        'api' => ['orders.read', 'orders.write'],
        'https://billing.test' => ['invoices.read'],
    ]);
});

test('creating, renaming and deleting a resource is recorded in the admin trail', function () {
    $resource = app(CreatesResource::class)->handle($this->realm, 'api', 'Orders API');

    app(UpdatesResource::class)->handle($resource, 'orders', 'Orders');

    expect($resource->refresh()->identifier)->toBe('orders');

    $updated = AdminEvent::forRealm($this->realm)->where('type', ResourceAdminEvent::ResourceUpdated->type())->sole();

    expect(data_get($updated->context, 'changes'))->toBe([
        'identifier' => ['old' => 'api', 'new' => 'orders'],
        'name' => ['old' => 'Orders API', 'new' => 'Orders'],
    ]);

    app(CreatesResourceScope::class)->handle($resource, 'orders.read', null);
    app(DeletesResource::class)->handle($resource);

    expect(Resource::query()->whereKey($resource->id)->exists())->toBeFalse()
        ->and(ResourceScope::query()->where('resource_id', $resource->id)->exists())->toBeFalse();

    $deleted = AdminEvent::forRealm($this->realm)->where('type', ResourceAdminEvent::ResourceDeleted->type())->sole();

    expect(data_get($deleted->context, 'identifier'))->toBe('orders')
        ->and(data_get($deleted->context, 'scopes-deleted'))->toBe(1);
});

test('changing and dropping a scope is recorded against its resource', function () {
    $resource = app(CreatesResource::class)->handle($this->realm, 'api', 'Orders API');
    $scope = app(CreatesResourceScope::class)->handle($resource, 'orders.read', null);

    app(UpdatesResourceScope::class)->handle($scope, 'orders.read', 'Read orders');

    expect($scope->refresh()->label())->toBe('Read orders');

    $updated = AdminEvent::forRealm($this->realm)->where('type', ResourceAdminEvent::ScopeUpdated->type())->sole();

    expect(data_get($updated->context, 'resource'))->toBe('api')
        ->and(data_get($updated->context, 'changes'))->toBe([
            'description' => ['old' => null, 'new' => 'Read orders'],
        ]);

    app(DeletesResourceScope::class)->handle($scope);

    expect($this->realm->refresh()->resources()->resources)->toBe(['api' => []]);
});
