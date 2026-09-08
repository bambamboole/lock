<?php

declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\renderedTabKeys;
use function Tests\Helpers\tenantMember;

it('renders every settings tab for an owner in priority order', function () {
    $tenant = Tenant::factory()->create();

    [$owner] = tenantMember(RolePreset::Owner, tenant: $tenant);

    $response = $this->actingAs($owner)
        ->get(route('tenant.settings.edit', ['current_tenant' => $tenant]))
        ->assertOk();

    expect(renderedTabKeys($response))->toBe(['general', 'members', 'roles', 'api-tokens', 'billing', 'activity', 'danger']);
});

it('renders only the unrestricted tabs for a plain member', function () {
    $tenant = Tenant::factory()->create();

    [$member] = tenantMember(RolePreset::Member, tenant: $tenant);

    $response = $this->actingAs($member)
        ->get(route('tenant.settings.edit', ['current_tenant' => $tenant]))
        ->assertOk();

    expect(renderedTabKeys($response))->toBe(['members', 'api-tokens', 'billing']);
});
