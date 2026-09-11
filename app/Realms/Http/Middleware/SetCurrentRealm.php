<?php
declare(strict_types=1);

namespace App\Realms\Http\Middleware;

use App\Realms\Models\Realm;
use App\Shared\Auth\Support\RequestUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the request's realm into the context every scoped query reads. On the
 * routes a realm serves it is the realm of the host; everywhere else it is the
 * signed-in user's own, and a request with no person behind it — a guest, or
 * a client acting for itself on the token guard — has none.
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

        Realm::forgetCurrent();
        RequestUser::of($request)?->realm->makeCurrent();

        return $next($request);
    }
}
