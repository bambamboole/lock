<?php

declare(strict_types=1);

use App\Tenants\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

use function Tests\Helpers\apiTokenFor;
use function Tests\Helpers\tenantMember;

beforeEach(function () {
    $this->createPersonalAccessClient();
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 1, 12, 0, 0));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @return array{0: string, 1: Tenant}
 */
function tokenForTenant(): array
{
    [$user, $tenant] = tenantMember(switch: false);

    return [apiTokenFor($user, $tenant, ['member:view']), $tenant];
}

it('allows up to the burst then returns 429 with rate-limit headers', function () {
    config(['billing.api-limits.trial' => ['rate' => 60, 'burst' => 2]]);
    [$plain] = tokenForTenant();

    $this->withToken($plain)->getJson('/api/v1/members')
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', '2')
        ->assertHeader('X-RateLimit-Remaining', '1')
        ->assertHeader('X-RateLimit-Reset', '1');

    $this->withToken($plain)->getJson('/api/v1/members')->assertOk();

    $this->withToken($plain)->getJson('/api/v1/members')
        ->assertStatus(429)
        ->assertHeader('X-RateLimit-Limit', '2')
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('X-RateLimit-Reset', '2')
        ->assertHeader('Retry-After')
        ->assertJsonPath('message', __('billing.rate-limit.exceeded'));
});

it('isolates buckets per tenant', function () {
    config(['billing.api-limits.trial' => ['rate' => 60, 'burst' => 1]]);
    [$plainA] = tokenForTenant();
    [$plainB] = tokenForTenant();

    $this->withToken($plainA)->getJson('/api/v1/members')->assertOk();
    $this->withToken($plainA)->getJson('/api/v1/members')->assertStatus(429);

    Auth::forgetGuards();
    $this->withToken($plainB)->getJson('/api/v1/members')->assertOk();
});
