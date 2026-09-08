<?php

declare(strict_types=1);

namespace App\Billing\Http\Middleware;

use App\Auth\Models\User;
use App\Billing\RateLimiting\TokenBucketRateLimiter;
use App\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ThrottleApiRequests
{
    public function __construct(private TokenBucketRateLimiter $limiter) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, int $cost = 1): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $tenant = $user->currentTenant;
        abort_unless($tenant instanceof Tenant, 403);

        /** @var array{rate: int, burst: int} $limits */
        $limits = config("billing.api-limits.{$tenant->apiLimitTier()}");
        $ratePerSecond = $limits['rate'] / 60;
        $burst = $limits['burst'];

        $result = $this->limiter->attempt("api:{$tenant->id}", $ratePerSecond, $burst, $cost);
        $reset = (int) ceil(($burst - $result->remaining) / $ratePerSecond);

        if (! $result->allowed) {
            return response()->json(['message' => __('billing.rate-limit.exceeded')], 429)
                ->withHeaders([
                    'Retry-After' => (int) ceil($result->retryAfter),
                    'X-RateLimit-Limit' => $result->limit,
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Reset' => $reset,
                ]);
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $result->limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $result->remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $reset);

        return $response;
    }
}
