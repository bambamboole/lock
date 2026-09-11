<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\ResourceScope;

interface UpdatesResourceScope
{
    public function handle(ResourceScope $scope, string $value, ?string $description): void;
}
