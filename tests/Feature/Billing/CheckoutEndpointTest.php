<?php

declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('an owner can start a checkout and receives paddle options', function (string $billingCycle) {
    Http::fake(['*' => Http::response(['data' => [[
        'id' => 'ctm_test', 'email' => 'owner@example.test', 'name' => 'Proj',
    ]]])]);

    [$owner, $tenant] = tenantMember();
    $priceId = config("billing.plans.starter.{$billingCycle}");

    $response = $this->actingAs($owner)
        ->postJson(route('billing.checkout', ['current_tenant' => $tenant->slug]), ['price_id' => $priceId])
        ->assertOk();

    $options = $response->json('options');

    expect($options)->toBeArray()
        ->and($options['items'])->toBe([['priceId' => $priceId, 'quantity' => 1]])
        ->and($options['settings']['successUrl'])->toBe(route('billing', ['current_tenant' => $tenant->slug]))
        ->and($options['customData']['subscription_type'])->toBe('default');
})->with(['monthly', 'yearly']);

test('a member without manage-billing permission cannot start a checkout', function () {
    [$member, $tenant] = tenantMember(RolePreset::Member);
    $priceId = config('billing.plans.starter.monthly');

    $this->actingAs($member)
        ->postJson(route('billing.checkout', ['current_tenant' => $tenant->slug]), ['price_id' => $priceId])
        ->assertForbidden();
});

test('an unknown price id is rejected', function () {
    [$owner, $tenant] = tenantMember();

    $this->actingAs($owner)
        ->postJson(route('billing.checkout', ['current_tenant' => $tenant->slug]), ['price_id' => 'pri_bogus'])
        ->assertStatus(422);
});

test('a tenant with an existing subscription cannot start a second checkout', function (string $status) {
    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, $status);

    $this->actingAs($owner)
        ->postJson(route('billing.checkout', ['current_tenant' => $tenant->slug]), [
            'price_id' => config('billing.plans.professional.monthly'),
        ])
        ->assertStatus(409);
})->with(['active', 'past_due']);
