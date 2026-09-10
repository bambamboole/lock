<?php
declare(strict_types=1);

namespace App\Realms\Http\Middleware;

use App\Realms\Models\Realm;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the request's realm into the context every scoped query reads. The
 * package's realm routes name it in the path; everywhere else the realm is
 * the signed-in user's own, and a guest has none.
 */
final class SetCurrentRealm
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $providerRealm = $request->attributes->get('oidc.realm');

        if (is_string($providerRealm)) {
            Realm::where('slug', $providerRealm)->firstOrFail()->makeCurrent();

            return $next($request);
        }

        Context::forget('realm_id');
        $request->user()?->realm->makeCurrent();

        return $next($request);
    }
}
