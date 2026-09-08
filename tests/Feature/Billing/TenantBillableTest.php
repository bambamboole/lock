<?php

declare(strict_types=1);

use App\Tenants\Models\Tenant;

use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('creating a tenant sets a future generic trial', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->trial_ends_at)->not->toBeNull()
        ->and($tenant->onGenericTrial())->toBeTrue()
        ->and($tenant->subscribedOrOnTrial())->toBeTrue();
});

test('tenant paddle identity uses the tenant name and owner email', function () {
    [$owner, $tenant] = tenantMember();
    $tenant->refresh();

    expect($tenant->paddleName())->toBe($tenant->name)
        ->and($tenant->paddleEmail())->toBe($owner->email);
});

test('current plan name maps an active price id back to its plan key', function () {
    [, $tenant] = subscribedOwner(paddleId: 'sub_test');

    expect($tenant->refresh()->currentPlanName())->toBe('starter');
});

test('current plan name defaults to free without a subscription', function () {
    expect(Tenant::factory()->create()->currentPlanName())->toBe('free');
});

test('current plan name defaults to free for a canceled and expired subscription', function () {
    $tenant = Tenant::factory()->create();

    $subscription = $tenant->subscriptions()->create([
        'type' => 'default',
        'paddle_id' => 'sub_canceled',
        'status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);
    $subscription->items()->create([
        'product_id' => 'pro_test',
        'price_id' => config('billing.plans.starter.monthly'),
        'status' => 'canceled',
        'quantity' => 1,
    ]);

    expect($tenant->refresh()->currentPlanName())->toBe('free');
});
