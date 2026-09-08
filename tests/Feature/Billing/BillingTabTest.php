<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Billing\Ui\Tables\TransactionsTable;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use Laravel\Paddle\Transaction;

use function Tests\Helpers\addMember;
use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\billingTabUrl;
use function Tests\Helpers\fakePaddleUnavailable;
use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('the retired billing url lands on the settings tab', function () {
    [$user, $tenant] = tenantMember();

    $this->actingAs($user)
        ->get(route('billing', ['current_tenant' => $tenant->slug]))
        ->assertRedirect(billingTabUrl($tenant));
});

test('the retired billing url is refused for a non-member', function () {
    [$user] = tenantMember();
    $other = Tenant::factory()->create();

    $this->actingAs($user)
        ->get(route('billing', ['current_tenant' => $other->slug]))
        ->assertForbidden();
});

test('the billing tab renders plans for a member', function () {
    fakePaddleUnavailable();
    [$user, $tenant] = tenantMember();

    $this->actingAs($user)
        ->get(billingTabUrl($tenant))
        ->assertOk()
        ->assertSee(__('billing.plans.starter.name'))
        ->assertSee(__('billing.plans.professional.name'));
});

test('the billing tab is reachable even without a subscription', function () {
    fakePaddleUnavailable();
    [$user, $tenant] = tenantMember();
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $this->actingAs($user)
        ->get(billingTabUrl($tenant))
        ->assertOk();
});

test('only a manager can see the transactions history on the billing tab', function () {
    fakePaddleUnavailable();
    [$owner, $tenant] = tenantMember(RolePreset::Owner);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Member);
    $member->switchTenant($tenant);

    Transaction::create([
        'billable_id' => $tenant->id,
        'billable_type' => Tenant::class,
        'paddle_id' => 'txn_visible_to_manager',
        'status' => 'completed',
        'total' => '1900',
        'tax' => '0',
        'currency' => 'EUR',
        'billed_at' => now(),
    ]);

    $this->actingAs($member->refresh())
        ->get(billingTabUrl($tenant))
        ->assertOk()
        ->assertDontSee('txn_visible_to_manager');

    $this->actingAs($owner)
        ->get(billingTabUrl($tenant))
        ->assertOk()
        ->assertSee('txn_visible_to_manager');
});

test('the transactions table only lists the current tenant transactions', function () {
    [$user, $tenant] = tenantMember();
    $other = Tenant::factory()->create();

    Transaction::create([
        'billable_id' => $tenant->id,
        'billable_type' => Tenant::class,
        'paddle_id' => 'txn_mine',
        'status' => 'completed',
        'total' => '1900',
        'tax' => '0',
        'currency' => 'EUR',
        'billed_at' => now(),
    ]);
    Transaction::create([
        'billable_id' => $other->id,
        'billable_type' => Tenant::class,
        'paddle_id' => 'txn_other',
        'status' => 'completed',
        'total' => '9900',
        'tax' => '0',
        'currency' => 'EUR',
        'billed_at' => now(),
    ]);

    $this->actingAs($user)
        ->loadTable(TransactionsTable::class, [], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('txn_mine')
        ->assertDontSee('txn_other');
});

test('the transactions table endpoint is refused without manage-billing', function () {
    [$member, $tenant] = tenantMember(RolePreset::Member, switch: false);

    $this->actingAs($member)
        ->loadDeniedTable(TransactionsTable::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('the billing tab offers the calls to action that match the subscription state', function (Closure $setup, Closure $visible, array $absent) {
    fakePaddleUnavailable();

    [$owner, $tenant] = $setup();

    $response = $this->actingAs($owner)
        ->get(billingTabUrl($tenant))
        ->assertOk();

    foreach ($visible($tenant) as $marker) {
        $response->assertSee($marker);
    }

    foreach ($absent as $marker) {
        $response->assertDontSee($marker);
    }
})->with([
    'subscribed' => [
        fn () => subscribedOwner(),
        fn (Tenant $tenant) => ['billing.subscription.switch-plan'],
        ['billing.checkout'],
    ],
    'past due' => [
        fn () => subscribedOwner(RolePreset::Owner, 'past_due'),
        fn (Tenant $tenant) => [__('billing.state.past-due.badge'), 'billing.subscription.switch-plan'],
        ['billing.checkout'],
    ],
    'unsubscribed' => [
        fn () => tenantMember(),
        fn (Tenant $tenant) => ['billing.checkout'],
        ['billing.subscription.switch-plan'],
    ],
    'canceling' => [
        fn () => subscribedOwner(RolePreset::Owner, 'active', now()->addWeek()),
        function (Tenant $tenant) {
            $endsAt = $tenant->subscriptions()->firstOrFail()->ends_at;
            assumeNotNull($endsAt);

            return [__('billing.state.canceling.badge', ['date' => $endsAt->translatedFormat('j F Y')])];
        },
        ['billing.subscription.switch-plan', 'billing.checkout'],
    ],
]);
