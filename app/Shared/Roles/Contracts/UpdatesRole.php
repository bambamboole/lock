<?php
declare(strict_types=1);

namespace App\Shared\Roles\Contracts;

use App\Roles\Models\Role;

interface UpdatesRole
{
    /**
     * @param  list<string>  $scopeIds  resource scope rows of the same realm
     */
    public function handle(Role $role, string $name, ?string $description, array $scopeIds): void;
}
