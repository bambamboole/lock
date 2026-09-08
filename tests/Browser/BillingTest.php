<?php

declare(strict_types=1);

use App\Tenants\Enums\RolePreset;

use function Tests\Helpers\primePaddlePrices;
use function Tests\Helpers\tenantMember;

it('mounts the paddle checkout widget on the billing page without javascript errors', function () {
    primePaddlePrices();

    [$owner, $tenant] = tenantMember(RolePreset::Owner);

    $this->actingAs($owner);

    $page = visit('/'.$tenant->slug.'/billing');

    $page->assertPresent('[data-testid="paddle-checkout"]')
        ->assertNoJavaScriptErrors();
});
