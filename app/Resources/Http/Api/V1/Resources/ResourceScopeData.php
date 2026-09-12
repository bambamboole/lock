<?php
declare(strict_types=1);

namespace App\Resources\Http\Api\V1\Resources;

use App\Resources\Models\ResourceScope;
use App\Shared\Data\Data;
use Bambamboole\Spectacular\Attributes\SpecProperty;
use Carbon\CarbonImmutable;

final class ResourceScopeData extends Data
{
    public function __construct(
        #[SpecProperty('Stable UUID of the scope, used in its API path.')]
        public string $id,
        #[SpecProperty('UUID of the resource this scope belongs to.')]
        public string $resourceId,
        #[SpecProperty('Scope value, unique within its resource.')]
        public string $value,
        #[SpecProperty('Human readable description of the scope.')]
        public ?string $description,
        #[SpecProperty('When the scope was created.')]
        public ?CarbonImmutable $createdAt,
        #[SpecProperty('When the scope was last changed.')]
        public ?CarbonImmutable $updatedAt,
    ) {}

    public static function fromResourceScope(ResourceScope $scope): self
    {
        return new self(
            id: $scope->id,
            resourceId: $scope->resource_id,
            value: $scope->value,
            description: $scope->description,
            createdAt: $scope->created_at,
            updatedAt: $scope->updated_at,
        );
    }
}
