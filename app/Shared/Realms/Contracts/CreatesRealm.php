<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;

interface CreatesRealm
{
    public function handle(string $name, string $domain, string $slug): Realm;
}
