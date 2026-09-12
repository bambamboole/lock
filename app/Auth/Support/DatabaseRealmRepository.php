<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;

final class DatabaseRealmRepository implements RealmRepository
{
    public function find(string $id): ?Realm
    {
        return Realm::query()->where('slug', $id)->first();
    }

    public function findByDomain(string $host): ?Realm
    {
        return Realm::findByHost($host);
    }
}
