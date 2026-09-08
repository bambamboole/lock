<?php

declare(strict_types=1);

namespace App\Billing\Http\Middleware;

use App\Auth\Models\User;
use App\Billing\Ui\Callouts\SubscriptionCallout;
use App\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Lattice\Facades\Effects;
use Lattice\Ui\Effects\Builtin\Callout;
use Symfony\Component\HttpFoundation\Response;

final class ShareSubscriptionCallout
{
    /**
     * A flashed callout is only drained by an Inertia page render, which
     * requests `text/html` and is therefore not caught by
     * `Request::expectsJson()`. Any request that either isn't a GET or
     * expects JSON — Lattice's own component endpoints, and the app's own
     * checkout POST called by plain `fetch` — never drains the flash bag,
     * so flashing on those would leave a stale callout for the next real
     * page render to pick up regardless of the current subscription state.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return $next($request);
        }

        $user = $request->user();
        $tenant = $this->resolveTenant($request);

        if ($user instanceof User && $tenant instanceof Tenant && $user->can('view', $tenant)) {
            $callout = SubscriptionCallout::for($tenant, $user);

            Effects::flash($callout ?? Callout::retract(SubscriptionCallout::KEY));
        }

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $routeTenant = $request->route('current_tenant');

        if ($routeTenant instanceof Tenant) {
            return $routeTenant;
        }

        $user = $request->user();

        return $user instanceof User ? $user->currentTenant : null;
    }
}
