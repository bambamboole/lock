<?php
declare(strict_types=1);

namespace App\Resources\Support;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Resources\Contracts\ProvidesRealmResources;
use Bambamboole\LaravelOidc\Server\Shared\Realms\Settings\ResourceSettings;
use Illuminate\Database\Eloquent\Collection;

/**
 * Registered as a scoped binding: laravel-oidc builds a fresh realm model on
 * every `RealmResolver::current()` call, so without a memo one token request
 * would re-read the resource tables for each of them. Scoped bindings are
 * dropped between requests, so an edit in the console takes effect on the next
 * one — unlike the permission catalog, which is code-derived and can only
 * change on a deploy, these rows change at runtime and are not worth a cache
 * that has to be invalidated.
 */
final class RealmResources implements ProvidesRealmResources
{
    /** @var array<string, Collection<int, \App\Resources\Models\Resource>> */
    private array $resources = [];

    public function for(Realm $realm): ResourceSettings
    {
        $resources = [];

        foreach ($this->resources($realm) as $resource) {
            $resources[$resource->identifier] = array_values(
                $resource->scopes->map(fn (ResourceScope $scope): string => $scope->value)->all(),
            );
        }

        return new ResourceSettings($resources);
    }

    /**
     * @return Collection<int, \App\Resources\Models\Resource>
     */
    public function resources(Realm $realm): Collection
    {
        return $this->resources[$realm->id] ??= $realm->realmResources()
            ->with('scopes')
            ->orderBy('identifier')
            ->get();
    }
}
