<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Realms\Http\Middleware\ResolveRealm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * On a route behind ResolveRealm the request host is the realm. Everywhere
 * else it is the realm something made current — `runAsCurrent()`, a queued
 * job, SetCurrentRealm — and before that the realm of the request host; the
 * console falls back to the master realm.
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

        $host = $request->getHost();
        $route = $request->route();

        if ($route instanceof Route && ResolveRealm::appliesTo($route)) {
            return Realm::findByHost($host) ?? throw new NotFoundHttpException("No realm is served from [{$host}].");
        }

        $context = Context::get('realm_id');

        if (is_string($context)) {
            return Realm::query()->with('storedSettings')->findOrFail($context);
        }

        return Realm::findByHost($host) ?? $this->find((string) config('oidc.realm', 'admin'));
    }

    private function find(string $id): Realm
    {
        $realm = $this->realms->find($id);

        return $realm instanceof Realm ? $realm : throw new NotFoundHttpException("Unknown realm [{$id}].");
    }
}
