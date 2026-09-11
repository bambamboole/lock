<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\Resource;

interface DeletesResource
{
    public function handle(Resource $resource): void;
}
