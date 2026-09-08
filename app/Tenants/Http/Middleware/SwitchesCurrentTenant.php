<?php
declare(strict_types=1);

namespace App\Tenants\Http\Middleware;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SwitchesCurrentTenant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('current_tenant');
        $user = $request->user();

        if ($tenant instanceof Tenant && $user instanceof User && ! $user->isCurrentTenant($tenant)) {
            $user->switchTenant($tenant);
        }

        return $next($request);
    }
}
