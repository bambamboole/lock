<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\RealmRole;

interface UpdatesRealmRole
{
    public function handle(RealmRole $role, string $name, ?string $description): void;
}
