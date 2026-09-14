<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\Resource;
use App\Shared\Resources\Data\UpdateResourceData;

interface UpdatesResource
{
    public function handle(Resource $resource, UpdateResourceData $data): void;
}
