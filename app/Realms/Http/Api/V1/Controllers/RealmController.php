<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Controllers;

use App\Realms\Http\Api\V1\Requests\StoreRealmRequest;
use App\Realms\Http\Api\V1\Requests\UpdateRealmRequest;
use App\Realms\Http\Api\V1\Resources\RealmResource;
use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use Bambamboole\Spectacular\QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response;

final readonly class RealmController
{
    public function __construct(
        private CreatesRealm $createRealm,
        private UpdatesRealmConfiguration $updateRealm,
        private UpdatesRealmDomain $updateDomain,
        private ChecksRealmDomain $checkDomain,
        private DeletesRealm $deleteRealm,
    ) {}

    /**
     * List realms
     *
     * Every realm this instance serves, the administration realm included.
     *
     * @return AnonymousResourceCollection<array-key, RealmResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return RealmResource::collection(
            QueryBuilder::for(Realm::class)
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('slug'),
                    AllowedFilter::exact('domain'),
                    AllowedFilter::exact('domain_status'),
                )
                ->allowedSorts('name', 'slug', 'created_at')
                ->defaultSort('name')
                ->apiPaginate(),
        );
    }

    /**
     * Show a realm
     */
    public function show(Realm $realm): RealmResource
    {
        return new RealmResource($realm);
    }

    /**
     * Create a realm
     *
     * The realm gets its own signing keypair, and its domain is checked right
     * away. A domain that does not reach this instance yet is reported through
     * `domain_status` rather than refused — DNS and the proxy usually follow.
     */
    public function store(StoreRealmRequest $request): JsonResponse
    {
        $realm = $this->createRealm->handle(
            $request->string('name')->toString(),
            $request->string('domain')->toString(),
            $request->string('slug')->toString(),
        );

        $this->checkDomain->handle($realm);

        return new RealmResource($realm->refresh())->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a realm
     *
     * A realm's slug is immutable. Re-pointing the domain changes the issuer of
     * every token the realm mints and restarts the domain check.
     */
    public function update(UpdateRealmRequest $request, Realm $realm): RealmResource
    {
        if ($request->has('name')) {
            $this->updateRealm->handle($realm, ['name' => $request->string('name')->toString()]);
        }

        if ($request->has('domain')) {
            $this->updateDomain->handle($realm, $request->string('domain')->toString());
            $this->checkDomain->handle($realm);
        }

        return new RealmResource($realm->refresh());
    }

    /**
     * Delete a realm
     *
     * Purges the realm's users, roles, clients, tokens and keys, and frees its
     * slug. The administration realm cannot be deleted.
     */
    public function destroy(Realm $realm): Response
    {
        abort_if($realm->isMaster(), Response::HTTP_CONFLICT, 'The administration realm cannot be deleted.');

        $this->deleteRealm->handle($realm);

        return response()->noContent();
    }
}
