<?php

declare(strict_types=1);

use App\Tenants\Enums\RolePreset;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\primePaddlePrices;
use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

it('resolves callout prose client-side in the viewer locale', function (
    string $locale,
    array $subscription,
    string $heading,
    string $body,
) {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 1, 12));

    try {
        primePaddlePrices();

        [$owner, $tenant] = tenantMember(RolePreset::Owner);
        $tenant->update(['name' => 'Acme Rockets']);
        $owner->update(['locale' => $locale]);

        $created = $tenant->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_browser_callout',
            ...$subscription,
        ]);
        $created->items()->create([
            'product_id' => 'pro_test',
            'price_id' => config('billing.plans.starter.monthly'),
            'status' => 'active',
            'quantity' => 1,
        ]);

        $this->actingAs($owner);

        $page = visit('/'.$tenant->slug.'/billing');

        $page->assertSee($heading)
            ->assertSee($body)
            ->assertDontSee('billing:state')
            ->assertDontSee('billing.state')
            ->assertNoJavaScriptErrors();
    } finally {
        CarbonImmutable::setTestNow();
    }
})->with([
    'past due in English' => [
        'en',
        ['status' => 'past_due'],
        'Payment failed',
        'We could not charge your payment method. Update it to keep Acme Rockets running.',
    ],
    'canceling in German' => [
        'de',
        ['status' => 'active', 'ends_at' => '2026-03-06'],
        'Abonnement endet',
        'Ihr Abonnement endet am 6. März 2026. Setzen Sie es fort, um den Zugriff zu behalten.',
    ],
]);

it('shows a past due banner and never stacks it across navigations', function () {
    primePaddlePrices();

    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'past_due');

    $this->actingAs($owner);

    $page = visit('/'.$tenant->slug.'/billing');

    $page->assertSee('Payment failed')
        ->assertCount('[data-test="callout-danger"]', 1);

    $page->click('Dashboard')
        ->assertCount('[data-test="callout-danger"]', 1)
        ->assertNoJavaScriptErrors();
});

it('drops the past due banner once the subscription is repaired', function () {
    primePaddlePrices();

    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'past_due');

    $this->actingAs($owner);

    $page = visit('/'.$tenant->slug.'/billing');

    $page->assertCount('[data-test="callout-danger"]', 1);

    $tenant->subscriptions()->sole()->update(['status' => 'active']);

    $page->click('Dashboard')
        ->assertMissing('[data-test="callout-danger"]')
        ->assertNoJavaScriptErrors();
});

it('clears the canceling banner once resuming the subscription reloads the same page', function () {
    primePaddlePrices();
    Http::fake(['*subscriptions/*' => Http::response(['data' => ['status' => 'active']])]);

    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'active', now()->addWeek(), paddleId: 'sub_browser_resume');

    $this->actingAs($owner);

    $page = visit('/'.$tenant->slug.'/billing');

    $page->assertPresent('[data-test="callout-warning"]');

    $page->click('[data-test="callout-warning"] [data-test="action-billing.subscription.resume"]')
        ->assertSee('Subscription resumed.')
        ->assertMissing('[data-test="callout-warning"]')
        ->assertNoJavaScriptErrors();
});
