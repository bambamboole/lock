<?php
declare(strict_types=1);

namespace App\Resources\Support;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Bambamboole\LaravelOidc\Server\Shared\Scopes\ScopeCatalog;
use Bambamboole\LaravelOidc\Server\Shared\Tokens\RealmAudiences;

/**
 * The OAuth scope catalog of the current realm, read from its resources. A
 * scope belongs to exactly one resource, so the same value under two resources
 * is two different scopes and only the requested one is served — which is what
 * keeps the realm from issuing a scope the addressed resource never declared.
 */
final readonly class ResourceScopeCatalog implements ScopeCatalog
{
    public function __construct(
        private RealmResolver $realms,
        private RealmAudiences $audiences,
        private RealmResources $resources,
    ) {}

    /**
     * @param  list<string>  $audiences
     * @return array<string, string>
     */
    public function scopes(array $audiences): array
    {
        $realm = $this->realms->current();

        if (! $realm instanceof Realm) {
            return [];
        }

        $catalog = [];

        foreach ($this->resources->resources($realm) as $resource) {
            if (! in_array($this->identifier($resource), $audiences, true)) {
                continue;
            }

            foreach ($resource->scopes as $scope) {
                $catalog[$scope->value] ??= $scope->label();
            }
        }

        return $catalog;
    }

    private function identifier(Resource $resource): string
    {
        return $resource->isAbsolute()
            ? $resource->identifier
            : $this->audiences->protectedResource($resource->identifier);
    }
}
