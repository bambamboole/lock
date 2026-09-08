<?php
declare(strict_types=1);

namespace App\Tenants\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantUrlDefaults
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->currentTenant?->makeCurrent();

        return $next($request);
    }
}
