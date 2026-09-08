<?php

declare(strict_types=1);

use App\Billing\Services\PricingService;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\fakePaddlePricePreview;
use function Tests\Helpers\fakePaddleUnavailable;

test('falls back to config prices when the paddle api is unavailable', function () {
    fakePaddleUnavailable();

    $plans = app(PricingService::class)->plans('monthly');

    expect($plans)->toHaveKeys(['free', 'starter', 'professional'])
        ->and($plans['starter']->rawPrice)->toBe(1900)
        ->and($plans['starter']->currency)->toBe('EUR')
        ->and($plans['free']->isPaid())->toBeFalse()
        ->and($plans['starter']->paddlePriceId)->toBe(config('billing.plans.starter.monthly'));
});

test('resolves plan prices from the paddle preview and caches them', function () {
    fakePaddlePricePreview([
        config('billing.plans.starter.monthly') => ['formatted' => '$25.00', 'raw' => 2500],
    ], 'USD');

    $pricing = app(PricingService::class);
    $plans = $pricing->plans('monthly');

    expect($plans['starter']->formattedPrice)->toBe('$25.00')
        ->and($plans['starter']->rawPrice)->toBe(2500)
        ->and($plans['starter']->currency)->toBe('USD')
        ->and($plans['professional']->rawPrice)->toBe(9900)
        ->and($plans['professional']->currency)->toBe('EUR');

    $pricing->plans('monthly');

    Http::assertSentCount(1);
});

test('the yearly cycle previews the yearly price ids', function () {
    fakePaddlePricePreview([
        config('billing.plans.starter.yearly') => ['formatted' => '$190.00', 'raw' => 19000],
    ], 'USD');

    $plans = app(PricingService::class)->plans('yearly');

    expect($plans['starter']->rawPrice)->toBe(19000)
        ->and($plans['starter']->paddlePriceId)->toBe(config('billing.plans.starter.yearly'));
});
