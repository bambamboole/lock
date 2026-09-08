<?php

declare(strict_types=1);

use App\Tenants\Models\Tenant;

use function Tests\Helpers\subscribedOwner;

test('a trialing tenant resolves the trial tier', function () {
    expect(Tenant::factory()->create()->apiLimitTier())->toBe('trial');
});

test('a lapsed tenant with no subscription resolves the free tier', function () {
    $tenant = Tenant::factory()->create();
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    expect($tenant->refresh()->apiLimitTier())->toBe('free');
});

test('a subscribed tenant resolves its plan tier', function () {
    [, $tenant] = subscribedOwner(paddleId: 'sub_tier');

    expect($tenant->refresh()->apiLimitTier())->toBe('starter');
});
