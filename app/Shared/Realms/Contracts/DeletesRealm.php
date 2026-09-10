<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;

interface DeletesRealm
{
    public function handle(Realm $realm): void;
}
