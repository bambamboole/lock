<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/**
 * @return array{0: User, 1: Tenant}
 */
function subscribedOwner(
    RolePreset $role = RolePreset::Owner,
    string $status = 'active',
    ?CarbonInterface $endsAt = null,
    bool $expiredTrial = false,
    ?string $paddleId = null,
): array {
    [$user, $tenant] = tenantMember($role);

    if ($expiredTrial) {
        $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();
    }

    $subscription = $tenant->subscriptions()->create([
        'type' => 'default',
        'paddle_id' => $paddleId ?? 'sub_manage',
        'status' => $status,
        'ends_at' => $endsAt,
    ]);
    $subscription->items()->create([
        'product_id' => 'pro_test',
        'price_id' => config('billing.plans.starter.monthly'),
        'status' => 'active',
        'quantity' => 1,
    ]);

    $user->refresh();

    return [$user, $tenant];
}

/**
 * A tenant past its trial holding a subscription with no items — the shape the
 * state machine reads when the plan itself is irrelevant.
 */
function tenantWithSubscription(string $status, ?string $endsAt = null): Tenant
{
    $tenant = Tenant::factory()->create(['trial_ends_at' => now()->subDay()]);

    $tenant->subscriptions()->create([
        'type' => 'default',
        'paddle_id' => 'sub_state',
        'status' => $status,
        'ends_at' => $endsAt,
    ]);

    return $tenant->refresh();
}

function fakePaddleUnavailable(): void
{
    Http::fake(fn () => Http::response(status: 500));
}

/**
 * A pricing-preview response covering only the given price ids; every other
 * requested price is absent from `line_items`, which is what drives a plan
 * onto its config fallback.
 *
 * @param  array<string, array{formatted: string, raw: int}>  $totalsByPriceId
 */
function fakePaddlePricePreview(array $totalsByPriceId, string $currency = 'USD'): void
{
    $lineItems = [];

    foreach ($totalsByPriceId as $priceId => $totals) {
        $lineItems[] = [
            'price' => ['id' => $priceId, 'unit_price' => ['currency_code' => $currency]],
            'formatted_totals' => ['total' => $totals['formatted']],
            'totals' => ['total' => $totals['raw']],
        ];
    }

    Http::fake(['*pricing-preview' => Http::response(['data' => ['details' => ['line_items' => $lineItems]]])]);
}

function fakePaddleSwap(): void
{
    Http::fake(['*subscriptions/sub_manage' => Http::response(['data' => [
        'status' => 'active',
        'items' => [],
    ]])]);
}

/**
 * Browser tests serve the built bundle against a real HTTP request, so the
 * price lookup cannot be faked through Http::fake — seed its cache instead.
 */
function primePaddlePrices(): void
{
    Cache::put('paddle-prices-monthly', [], 300);
}

function billingTabUrl(Tenant $tenant): string
{
    return route('tenant.settings.edit', ['current_tenant' => $tenant->slug, 'tabs' => 'billing']);
}

/**
 * @return TestResponse<Response>
 */
function billingTabResponse(User $user, Tenant $tenant): TestResponse
{
    fakePaddleUnavailable();

    return runningTestCase()->actingAs($user)->get(billingTabUrl($tenant));
}

/**
 * @return array<string, mixed>|null
 */
function billingTabCallout(User $user, Tenant $tenant): ?array
{
    return runningTestCase()->assertLatticeEffects(billingTabResponse($user, $tenant))->props('callout');
}
