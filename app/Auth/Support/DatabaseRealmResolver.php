<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmRoutes;
use Bambamboole\LaravelOidc\Server\Realms\Http\Middleware\ResolveRealm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * On the routes a realm serves the request host is the realm. Everywhere else
 * — the console, queued jobs, console commands — it is whatever realm is
 * current, and the master realm when none is.
 */
final readonly class DatabaseRealmResolver implements RealmResolver
{
    public function __construct(private RealmRepository $realms) {}

    public function current(): Realm
    {
        $request = request();
        $id = $request->attributes->get(ResolveRealm::ATTRIBUTE);

        if (is_string($id) && $id !== '') {
            return $this->find($id);
        }

        if (RealmRoutes::serves($request)) {
            $host = $request->getHost();

            return Realm::findByHost($host) ?? throw new NotFoundHttpException("No realm is served from [{$host}].");
        }

        $context = Context::get('realm_id');

        if (is_string($context)) {
            return Realm::query()->with('storedSettings')->findOrFail($context);
        }

        return $this->find((string) config('oidc.realm', 'admin'));
    }

    private function find(string $id): Realm
    {
        $realm = $this->realms->find($id);

        return $realm instanceof Realm ? $realm : throw new NotFoundHttpException("Unknown realm [{$id}].");
    }
}
