<?php

declare(strict_types=1);

namespace App\Realms\Support;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmRoutes;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Decides, once the route is known, whether the host may serve it. A host no
 * realm is served from gets nothing, which also keeps a forged Host header out
 * of every URL the app would otherwise mint from it (password reset links).
 * The master realm's host serves everything; any other realm's host serves
 * only what that realm offers its users, and its root is the account page.
 */
final class RealmHostGate
{
    public function guard(Request $request, Route $route): void
    {
        if ($route->uri() === 'up' || $route->named(...RealmRoutes::SHARED)) {
            return;
        }

        $realm = Realm::findByHost($request->getHost()) ?? throw new NotFoundHttpException;

        // WebAuthn binds a passkey to the host it was created on.
        config([
            'passkeys.relying_party_id' => $realm->host(),
            'passkeys.allowed_origins' => [$realm->origin()],
        ]);

        if ($realm->isMaster() || $route->named(...RealmRoutes::SERVED)) {
            return;
        }

        if ($route->named('home')) {
            throw new HttpResponseException(to_route('account'));
        }

        throw new NotFoundHttpException;
    }
}
