<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;

interface CreatesResource
{
    public function handle(Realm $realm, string $identifier, string $name): Resource;
}
