<?php
declare(strict_types=1);

namespace App\Realms\Http\Middleware;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\URL;
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
        $user = $request->user();

        if ($user instanceof User) {
            $slug = $request->route('current_realm');
            $realm = is_string($slug) ? Realm::where('slug', $slug)->firstOrFail() : $user->realm;
            abort_unless($user->canAccessRealm($realm), 404);
            $realm->makeCurrent();
            URL::defaults(['current_realm' => $realm->slug, 'realm' => $user->realm->slug]);
        }

        return $next($request);
    }
}
