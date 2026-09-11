<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;

final class DatabaseRealmRepository implements RealmRepository
{
    public function find(string $id): ?Realm
    {
        return Realm::query()->with('storedSettings')->where('slug', $id)->first();
    }

    /**
     * Lock resolves realms from the request path, never the host: a host-derived
     * realm would isolate each realm behind its own origin and its own session
     * cookie, which is a different security model than the path routing the app
     * is built on. See .ai/rules/realms.md.
     */
    public function findByDomain(string $host): ?Realm
    {
        return null;
    }
}
