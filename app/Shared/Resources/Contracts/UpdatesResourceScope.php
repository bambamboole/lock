<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\ResourceScope;
use App\Shared\Resources\Data\UpdateResourceScopeData;

interface UpdatesResourceScope
{
    public function handle(ResourceScope $scope, UpdateResourceScopeData $data): void;
}
