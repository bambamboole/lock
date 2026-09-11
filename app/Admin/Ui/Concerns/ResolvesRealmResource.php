<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;

/**
 * A resource is only reachable through its own realm, and a scope only through
 * its own resource: the `resource` or `resourceScope` context alone would let a
 * signed reference from one realm's console address another realm's rows.
 */
trait ResolvesRealmResource
{
    private function realm(): Realm
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return $realm;
    }

    private function resource(): Resource
    {
        $resource = $this->resourceOrNull();

        abort_unless($resource instanceof Resource, 404);

        return $resource;
    }

    private function resourceOrNull(): ?Resource
    {
        $realm = $this->contextModelOrNull('realm');
        $resource = $this->contextModelOrNull('resource');

        return $realm instanceof Realm && $resource instanceof Resource && $resource->belongsToRealm($realm) ? $resource : null;
    }

    private function resourceScope(): ResourceScope
    {
        $scope = $this->resourceScopeOrNull();

        abort_unless($scope instanceof ResourceScope, 404);

        return $scope;
    }

    private function resourceScopeOrNull(): ?ResourceScope
    {
        $resource = $this->resourceOrNull();
        $scope = $this->contextModelOrNull('resourceScope');

        return $resource instanceof Resource && $scope instanceof ResourceScope && $scope->belongsToResource($resource) ? $scope : null;
    }
}
