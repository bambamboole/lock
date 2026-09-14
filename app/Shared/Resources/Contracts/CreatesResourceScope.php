<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Resources\Data\CreateResourceScopeData;

interface CreatesResourceScope
{
    public function handle(Resource $resource, CreateResourceScopeData $data): ResourceScope;
}
