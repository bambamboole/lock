<?php

declare(strict_types=1);

namespace App\Billing\Http\Middleware;

use App\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantIsSubscribed
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('tenant.settings.edit')) {
            return $next($request);
        }

        $tenant = $request->route('current_tenant');

        if ($tenant instanceof Tenant && ! $tenant->subscribedOrOnTrial()) {
            return to_route('billing', ['current_tenant' => $tenant->slug]);
        }

        return $next($request);
    }
}
