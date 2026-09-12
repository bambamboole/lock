<?php
declare(strict_types=1);

namespace App\Shared\Roles\Contracts;

use App\Roles\Models\Role;

interface DeletesRole
{
    public function handle(Role $role): void;
}
