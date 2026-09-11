<?php
declare(strict_types=1);

namespace App\Shared\Resources\Contracts;

use App\Resources\Models\ResourceScope;

interface DeletesResourceScope
{
    public function handle(ResourceScope $scope): void;
}
