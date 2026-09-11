<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;

interface CreatesResourceScope
{
    public function handle(Resource $resource, string $value, ?string $description): ResourceScope;
}
