<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\Resource;

interface UpdatesResource
{
    public function handle(Resource $resource, string $identifier, string $name): void;
}
