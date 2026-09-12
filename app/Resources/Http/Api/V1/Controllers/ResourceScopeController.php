<?php
declare(strict_types=1);

namespace App\Resources\Http\Api\V1\Controllers;

use App\Realms\Models\Realm;
use App\Resources\Http\Api\V1\Resources\ResourceScopeData;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Api\ManagementApi;
use App\Shared\Resources\Contracts\CreatesResourceScope;
use App\Shared\Resources\Contracts\DeletesResourceScope;
use App\Shared\Resources\Contracts\UpdatesResourceScope;
use App\Shared\Resources\Data\CreateResourceScopeData;
use App\Shared\Resources\Data\UpdateResourceScopeData;
use Bambamboole\Spectacular\QueryBuilder;
use Dedoc\Scramble\Attributes\IgnoreResponse;
use Dedoc\Scramble\Attributes\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class ResourceScopeController
{
    public function __construct(
        private CreatesResourceScope $createScope,
        private UpdatesResourceScope $updateScope,
        private DeletesResourceScope $deleteScope,
        private ManagementApi $api,
    ) {}

    /** @return PaginatedDataCollection<array-key, ResourceScopeData> */
    public function index(Realm $realm, string $resource): PaginatedDataCollection
    {
        $scopes = QueryBuilder::for($this->resource($realm, $resource)->scopes())
            ->allowedFilters(AllowedFilter::exact('value'), AllowedFilter::partial('description'))
            ->allowedSorts('value', 'description', 'created_at')
            ->defaultSort('value')
            ->apiPaginate();

        return ResourceScopeData::collect($scopes, PaginatedDataCollection::class);
    }

    public function show(Realm $realm, string $resource, string $scope): ResourceScopeData
    {
        return ResourceScopeData::fromResourceScope($this->scope($realm, $resource, $scope));
    }

    #[IgnoreResponse(200)]
    #[Response(201, type: ResourceScopeData::class)]
    public function store(CreateResourceScopeData $data, Realm $realm, string $resource): SymfonyResponse
    {
        $resource = $this->resource($realm, $resource);
        $this->guardManagementApi($resource);

        return ResourceScopeData::fromResourceScope($this->createScope->handle($resource, $data))
            ->toResponse(request())->setStatusCode(SymfonyResponse::HTTP_CREATED);
    }

    public function update(UpdateResourceScopeData $data, Realm $realm, string $resource, string $scope): ResourceScopeData
    {
        $scope = $this->scope($realm, $resource, $scope);
        $this->guardManagementApi($scope->resource);
        $this->updateScope->handle($scope, $data);

        return ResourceScopeData::fromResourceScope($scope->refresh());
    }

    public function destroy(Realm $realm, string $resource, string $scope): SymfonyResponse
    {
        $scope = $this->scope($realm, $resource, $scope);
        $this->guardManagementApi($scope->resource);
        $this->deleteScope->handle($scope);

        return response()->noContent();
    }

    private function resource(Realm $realm, string $resource): Resource
    {
        return $realm->realmResources()->findOrFail($resource);
    }

    private function scope(Realm $realm, string $resource, string $scope): ResourceScope
    {
        return $this->resource($realm, $resource)->scopes()->findOrFail($scope);
    }

    private function guardManagementApi(Resource $resource): void
    {
        abort_if($this->api->owns($resource), SymfonyResponse::HTTP_FORBIDDEN, 'The management API resource is reconciled by app:deploy and cannot be changed here.');
    }
}
