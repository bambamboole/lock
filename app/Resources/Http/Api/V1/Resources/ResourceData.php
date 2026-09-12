<?php
declare(strict_types=1);

namespace App\Resources\Http\Api\V1\Resources;

use App\Resources\Models\Resource;
use App\Shared\Data\Data;
use Bambamboole\Spectacular\Attributes\SpecProperty;
use Carbon\CarbonImmutable;

final class ResourceData extends Data
{
    public function __construct(
        #[SpecProperty('The UUID of the protected resource, used in this API’s paths.')]
        public string $id,
        #[SpecProperty('The slug of the realm the resource belongs to.')]
        public string $realm,
        #[SpecProperty('A path relative to the realm issuer, or an absolute URI naming the resource server.')]
        public string $identifier,
        #[SpecProperty('Human readable name of the protected resource.')]
        public string $name,
        #[SpecProperty('When the resource was created.')]
        public ?CarbonImmutable $createdAt,
        #[SpecProperty('When the resource was last changed.')]
        public ?CarbonImmutable $updatedAt,
    ) {}

    public static function fromResource(Resource $resource): self
    {
        return new self(
            id: $resource->id,
            realm: $resource->realm->slug,
            identifier: $resource->identifier,
            name: $resource->name,
            createdAt: $resource->created_at,
            updatedAt: $resource->updated_at,
        );
    }
}
