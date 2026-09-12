<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Realms\Data\CreateRealmData;

interface CreatesRealm
{
    public function handle(CreateRealmData $data): Realm;
}
