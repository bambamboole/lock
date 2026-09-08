<?php
declare(strict_types=1);

namespace App\Realms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the signed-in user's realm into the context every scoped query and
 * queued job reads. The realm comes from the user because a user has exactly
 * one; once realms are resolved from the request host, guests get one too and
 * this reads the host instead.
 */
final class SetCurrentRealm
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->realm->makeCurrent();

        return $next($request);
    }
}
