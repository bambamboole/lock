<?php

declare(strict_types=1);

use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('a tenant on trial can reach the dashboard', function () {
    [$user, $tenant] = tenantMember();

    $this->actingAs($user)
        ->get(route('dashboard', ['current_tenant' => $tenant->slug]))
        ->assertOk();
});

test('a tenant without subscription or trial is redirected off the dashboard', function () {
    [$user, $tenant] = tenantMember();
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $this->actingAs($user)
        ->get(route('dashboard', ['current_tenant' => $tenant->slug]))
        ->assertRedirect();
});

test('an active subscription can reach the dashboard', function () {
    [$user, $tenant] = subscribedOwner(expiredTrial: true, paddleId: 'sub_active');

    $this->actingAs($user)
        ->get(route('dashboard', ['current_tenant' => $tenant->slug]))
        ->assertOk();
});

test('a past due subscription can still reach the dashboard', function () {
    [$user, $tenant] = subscribedOwner(status: 'past_due', expiredTrial: true, paddleId: 'sub_past_due');

    $this->actingAs($user)
        ->get(route('dashboard', ['current_tenant' => $tenant->slug]))
        ->assertOk();
});

test('a past due subscription keeps its paid plan and api tier', function () {
    [, $tenant] = subscribedOwner(status: 'past_due', expiredTrial: true, paddleId: 'sub_past_due_tier');

    $tenant->refresh();

    expect($tenant->currentPlanName())->toBe('starter')
        ->and($tenant->apiLimitTier())->toBe('starter');
});
