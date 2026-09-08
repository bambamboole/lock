<?php

declare(strict_types=1);

use App\Billing\RateLimiting\TokenBucketRateLimiter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 1, 12, 0, 0));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('allows up to the burst then denies', function () {
    $limiter = new TokenBucketRateLimiter;

    for ($i = 0; $i < 3; $i++) {
        expect($limiter->attempt('k', 1.0, 3)->allowed)->toBeTrue();
    }

    $result = $limiter->attempt('k', 1.0, 3);

    expect($result->allowed)->toBeFalse()
        ->and($result->limit)->toBe(3)
        ->and($result->remaining)->toBe(0)
        ->and($result->retryAfter)->toBeGreaterThan(0.0);
});

it('refills over time at the configured rate, capped at the burst', function () {
    $limiter = new TokenBucketRateLimiter;

    for ($i = 0; $i < 3; $i++) {
        $limiter->attempt('k', 1.0, 3);
    }
    expect($limiter->attempt('k', 1.0, 3)->allowed)->toBeFalse();

    CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(2));
    expect($limiter->attempt('k', 1.0, 3)->allowed)->toBeTrue()
        ->and($limiter->attempt('k', 1.0, 3)->allowed)->toBeTrue()
        ->and($limiter->attempt('k', 1.0, 3)->allowed)->toBeFalse();

    CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(100));
    expect($limiter->attempt('k', 1.0, 3)->remaining)->toBe(2);
});

it('charges the given cost and computes retryAfter from the deficit', function () {
    $limiter = new TokenBucketRateLimiter;

    expect($limiter->attempt('k', 1.0, 10, 4)->allowed)->toBeTrue()
        ->and($limiter->attempt('k', 1.0, 10, 4)->allowed)->toBeTrue();

    $result = $limiter->attempt('k', 1.0, 10, 4);

    expect($result->allowed)->toBeFalse()
        ->and($result->retryAfter)->toEqualWithDelta(2.0, 0.01);
});

it('fails open instead of stalling when the bucket lock is contended', function () {
    // Lock::block() measures its timeout with Carbon; a frozen test clock
    // would make the contended lock spin forever.
    CarbonImmutable::setTestNow();

    $limiter = new TokenBucketRateLimiter;

    $lock = Cache::lock('ratelimit-lock:k', 5);
    expect($lock->get())->toBeTrue();

    try {
        $start = microtime(true);
        $result = $limiter->attempt('k', 1.0, 3);

        expect($result->allowed)->toBeTrue()
            ->and(microtime(true) - $start)->toBeLessThan(3.0);
    } finally {
        $lock->release();
    }
});

it('isolates keys', function () {
    $limiter = new TokenBucketRateLimiter;

    for ($i = 0; $i < 3; $i++) {
        $limiter->attempt('a', 1.0, 3);
    }
    expect($limiter->attempt('a', 1.0, 3)->allowed)->toBeFalse()
        ->and($limiter->attempt('b', 1.0, 3)->allowed)->toBeTrue();
});
