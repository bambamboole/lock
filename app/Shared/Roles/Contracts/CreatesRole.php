<?php
declare(strict_types=1);

namespace App\Shared\Roles\Contracts;

use App\Realms\Models\Realm;
use App\Roles\Models\Role;

interface CreatesRole
{
    /**
     * @param  list<string>  $scopeIds  resource scope rows of the same realm
     */
    public function handle(Realm $realm, string $name, ?string $description, array $scopeIds): Role;
}
