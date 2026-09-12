<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Shared\Resources\Data\CreateResourceData;

interface CreatesResource
{
    public function handle(Realm $realm, CreateResourceData $data): Resource;
}
