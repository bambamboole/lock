<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;

interface CreatesRealmRole
{
    public function handle(Realm $realm, string $name, ?string $description): RealmRole;
}
