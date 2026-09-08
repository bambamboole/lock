<?php

declare(strict_types=1);

use App\Billing\Ui\Actions\SwitchPlan;
use App\Tenants\Enums\RolePreset;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\fakePaddleSwap;
use function Tests\Helpers\subscribedOwner;

test('an upgrade bills immediately with proration', function () {
    fakePaddleSwap();

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(SwitchPlan::class, [], ['tenant' => $tenant->slug, 'plan' => 'professional'])
        ->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request['proration_billing_mode'] === 'prorated_immediately');
});

test('a downgrade defers to the next billing period', function () {
    fakePaddleSwap();

    [$owner, $tenant] = subscribedOwner();
    $tenant->subscriptions()->firstOrFail()->items()->update([
        'price_id' => config('billing.plans.professional.monthly'),
    ]);

    $this->actingAs($owner->refresh())
        ->callAction(SwitchPlan::class, [], ['tenant' => $tenant->slug, 'plan' => 'starter'])
        ->assertOk();

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request['proration_billing_mode'] === 'prorated_next_billing_period');
});

test('a member without manage-billing permission cannot switch plans', function () {
    [$member, $tenant] = subscribedOwner(RolePreset::Member);

    $this->actingAs($member)
        ->callDeniedAction(SwitchPlan::class, [], ['tenant' => $tenant->slug, 'plan' => 'professional'])
        ->assertForbidden();
});

test('an unknown plan key is rejected', function () {
    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(SwitchPlan::class, [], ['tenant' => $tenant->slug, 'plan' => 'bogus'])
        ->assertNotFound();
});

test('the free plan cannot be switched to', function () {
    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(SwitchPlan::class, [], ['tenant' => $tenant->slug, 'plan' => 'free'])
        ->assertNotFound();
});
