<?php

declare(strict_types=1);

namespace App\Billing\RateLimiting;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class TokenBucketRateLimiter
{
    public function attempt(string $key, float $ratePerSecond, int $burst, int $cost = 1): RateLimitResult
    {
        try {
            return $this->consume($key, $ratePerSecond, $burst, $cost);
        } catch (LockTimeoutException) {
            // ponytail: fail open under lock contention instead of stalling the
            // request; switch to an atomic Redis/Lua path if precise limiting
            // under heavy concurrency starts to matter.
            return new RateLimitResult(allowed: true, limit: $burst, remaining: 0, retryAfter: 0.0);
        }
    }

    private function consume(string $key, float $ratePerSecond, int $burst, int $cost): RateLimitResult
    {
        return Cache::lock("ratelimit-lock:{$key}", 5)->block(1, function () use ($key, $ratePerSecond, $burst, $cost): RateLimitResult {
            $stateKey = "ratelimit:{$key}";
            $now = CarbonImmutable::now()->getPreciseTimestamp() / 1_000_000;

            /** @var array{tokens: float, ts: float}|null $state */
            $state = Cache::get($stateKey);
            $tokens = $state['tokens'] ?? (float) $burst;
            $ts = $state['ts'] ?? $now;

            $tokens = min((float) $burst, $tokens + ($now - $ts) * $ratePerSecond);

            $allowed = $tokens >= $cost;
            $retryAfter = 0.0;

            if ($allowed) {
                $tokens -= $cost;
            } else {
                $retryAfter = ($cost - $tokens) / $ratePerSecond;
            }

            $ttl = (int) ceil($burst / $ratePerSecond) + 1;
            Cache::put($stateKey, ['tokens' => $tokens, 'ts' => $now], $ttl);

            return new RateLimitResult(
                allowed: $allowed,
                limit: $burst,
                remaining: (int) floor($tokens),
                retryAfter: $retryAfter,
            );
        });
    }
}
