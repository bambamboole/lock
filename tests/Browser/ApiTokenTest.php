<?php
declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\tenantMember;

beforeEach(fn () => $this->createPersonalAccessClient());

it('reveals a newly created API token in a modal', function () {
    $tenant = Tenant::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    [$user] = tenantMember(RolePreset::Owner, tenant: $tenant);

    $this->actingAs($user);

    $page = visit('/tenants/acme/settings?tabs=api-tokens');

    $page
        // Modal starts closed.
        ->assertDontSee('Your new token')
        ->fill('input[name="name"]', 'My CLI token')
        ->click('button[type="submit"]')
        // The reveal modal opens (Modal::open() is reactive in Lattice ≥0.12).
        ->assertSee('Your new token')
        ->assertSee('Copy it now')
        // The issued token (a JWT) is shown for copying.
        ->assertSee('eyJ')
        // Creation lands back on the api-tokens tab, keeping the in-tab reveal URL-consistent.
        ->assertQueryStringHas('tabs', 'api-tokens')
        ->assertNoJavaScriptErrors();
});
