<?php

declare(strict_types=1);

use App\Billing\Enums\SubscriptionState;
use App\Tenants\Models\Tenant;

use function Tests\Helpers\tenantWithSubscription;

test('a subscription status maps to a state', function (string $status, bool $scheduledCancellation, SubscriptionState $expected) {
    $tenant = tenantWithSubscription($status, $scheduledCancellation ? now()->addWeek()->toDateTimeString() : null);

    expect(SubscriptionState::for($tenant))->toBe($expected);
})->with([
    'active' => ['active', false, SubscriptionState::Active],
    'past due' => ['past_due', false, SubscriptionState::PastDue],
    'active with a scheduled cancellation' => ['active', true, SubscriptionState::Canceling],
    'past due with a scheduled cancellation' => ['past_due', true, SubscriptionState::PastDue],
]);

test('a trial window maps to a state', function (Closure $trialEndsAt, SubscriptionState $expected) {
    config(['billing.trial-warning-days' => 3]);
    $tenant = Tenant::factory()->create(['trial_ends_at' => $trialEndsAt()]);

    expect(SubscriptionState::for($tenant))->toBe($expected);
})->with([
    'well before the warning window' => [fn () => now()->addDays(14), SubscriptionState::Trialing],
    'just outside the warning cutoff' => [fn () => now()->addDays(3)->addMinutes(5), SubscriptionState::Trialing],
    'just inside the warning cutoff' => [fn () => now()->addDays(3)->subMinutes(5), SubscriptionState::TrialEnding],
    'a day out' => [fn () => now()->addDay(), SubscriptionState::TrialEnding],
    'already over' => [fn () => now()->subDay(), SubscriptionState::Expired],
]);

test('a subscription outranks a running trial', function () {
    $tenant = Tenant::factory()->create(['trial_ends_at' => now()->addDay()]);
    $tenant->subscriptions()->create([
        'type' => 'default',
        'paddle_id' => 'sub_over_trial',
        'status' => 'past_due',
    ]);

    expect(SubscriptionState::for($tenant->refresh()))->toBe(SubscriptionState::PastDue);
});
