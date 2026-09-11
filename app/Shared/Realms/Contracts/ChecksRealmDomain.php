<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Realms\Enums\RealmDomainStatus;

interface ChecksRealmDomain
{
    /**
     * Probes whether the realm's domain reaches this instance and records the
     * outcome on the realm.
     */
    public function handle(Realm $realm): RealmDomainStatus;
}
