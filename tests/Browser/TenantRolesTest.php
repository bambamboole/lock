<?php
declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\tenantMember;

it('opens the create role form from the roles tab', function () {
    $tenant = Tenant::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    [$user] = tenantMember(RolePreset::Owner, tenant: $tenant);

    $this->actingAs($user);

    $page = visit('/tenants/acme/settings?tabs=roles');

    $page->assertMissing('input[name="name"]')
        ->click('[data-test="create-role"]')
        ->assertVisible('input[name="name"]')
        ->assertNoJavaScriptErrors();
});
