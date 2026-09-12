<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\RealmRole;

interface UpdatesRealmRole
{
    /**
     * @param  list<string>  $scopeIds  resource scope rows of the same realm
     */
    public function handle(RealmRole $role, string $name, ?string $description, array $scopeIds): void;
}
