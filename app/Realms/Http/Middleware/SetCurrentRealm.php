<?php
declare(strict_types=1);

namespace App\Realms\Http\Middleware;

use App\Realms\Models\Realm;
use App\Shared\Auth\Support\RequestUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

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
        RequestUser::of($request)?->realm->makeCurrent();

        return $next($request);
    }
}
