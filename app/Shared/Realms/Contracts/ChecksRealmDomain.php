<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Realms\Enums\RealmDomainStatus;

interface ChecksRealmDomain
{
    public function handle(Realm $realm): RealmDomainStatus;
}
