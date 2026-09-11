<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ScopeRepository;
use Bambamboole\LaravelOidc\Server\Scopes\Scope;
use Bambamboole\LaravelOidc\Server\Shared\Realms\IssuerResolver;

use function Tests\Helpers\clientCredentialsToken;
use function Tests\Helpers\jwtClaims;

/**
 * A scope belongs to exactly one resource: the same value under two of them is
 * two different scopes, told apart by the RFC 8707 `resource` the token is
 * requested for.
 */
beforeEach(function () {
    $this->realm = Realm::master();
    $this->issuer = app(IssuerResolver::class)->url();

    $orders = Resource::factory()->for($this->realm)->create(['identifier' => 'orders', 'name' => 'Orders']);
    $orders->scopes()->create(['value' => 'read', 'description' => 'Read orders']);
    $orders->scopes()->create(['value' => 'orders.write', 'description' => null]);

    $reports = Resource::factory()->for($this->realm)->create(['identifier' => 'reports', 'name' => 'Reports']);
    $reports->scopes()->create(['value' => 'read', 'description' => 'Read reports']);

    $this->orders = $this->issuer.'/orders';
    $this->reports = $this->issuer.'/reports';

    $this->client = $this->createOidcMachineClient();
    $this->client->forceFill(['allowed_exchange_audiences' => [$this->orders, $this->reports]])->save();
});

test('the catalog serves only the scopes of the requested resource', function () {
    $catalog = $this->realm->runAsCurrent(
        fn (): array => app(ScopeRepository::class)->all([$this->orders])
            ->mapWithKeys(fn (Scope $scope): array => [$scope->id => $scope->description])
            ->all(),
    );

    expect($catalog)->toHaveKeys(['read', 'orders.write'])
        ->and($catalog['read'])->toBe('Read orders')
        ->and($catalog['orders.write'])->toBe('orders.write');
});

test('two resources carry the same scope value without borrowing each other\'s meaning', function () {
    $forOrders = clientCredentialsToken($this, ['resource' => $this->orders, 'scope' => 'read'])->assertOk();
    $forReports = clientCredentialsToken($this, ['resource' => $this->reports, 'scope' => 'read'])->assertOk();

    expect($forOrders->json('scope'))->toBe('read')
        ->and($forReports->json('scope'))->toBe('read')
        ->and(jwtClaims($forOrders->json('access_token'))['aud'])->toBe($this->orders)
        ->and(jwtClaims($forReports->json('access_token'))['aud'])->toBe($this->reports);

    $descriptions = $this->realm->runAsCurrent(fn (): array => [
        app(ScopeRepository::class)->find('read', [$this->orders])?->description,
        app(ScopeRepository::class)->find('read', [$this->reports])?->description,
    ]);

    expect($descriptions)->toBe(['Read orders', 'Read reports']);
});

test('a scope of a resource the request did not ask for is refused', function () {
    clientCredentialsToken($this, ['resource' => $this->reports, 'scope' => 'orders.write'])
        ->assertStatus(400)
        ->assertJson(['error' => 'invalid_scope']);
});

test('a resource scope is not issuable without naming its resource', function () {
    clientCredentialsToken($this, ['scope' => 'orders.write'])
        ->assertStatus(400)
        ->assertJson(['error' => 'invalid_scope']);

    expect(clientCredentialsToken($this, ['scope' => 'openid'])->assertOk()->json('scope'))->toBe('openid');
});

test('a realm with no resources still issues the standard oidc scopes', function () {
    Resource::query()->delete();

    expect(clientCredentialsToken($this, ['scope' => 'openid profile'])->assertOk()->json('scope'))->toBe('openid profile');
});
