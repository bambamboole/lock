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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
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
    public function index(): AnonymousResourceCollection
    {
        $realms = QueryBuilder::for(Realm::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('slug'),
                AllowedFilter::exact('domain'),
                AllowedFilter::exact('domain_status'),
            )
            ->allowedSorts('name', 'slug', 'created_at')
            ->defaultSort('name')
            ->apiPaginate();

        // Every realm serialises its configuration, which reads its settings rows.
        Collection::make($realms->items())->load('storedSettings');

        return RealmResource::collection($realms);
    }

    /**
     * Show a realm
     */
    public function show(Realm $realm): RealmResource
    {
        return $this->resource($realm);
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

        return $this->resource($realm->refresh())->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a realm
     *
     * A realm's slug is immutable. Settings left out of `configuration` keep
     * their current value. Re-pointing the domain changes the issuer of every
     * token the realm mints and restarts the domain check.
     */
    public function update(UpdateRealmRequest $request, Realm $realm): RealmResource
    {
        $submitted = [
            ...$request->has('name') ? ['name' => $request->string('name')->toString()] : [],
            ...$request->array('configuration'),
        ];

        if ($submitted !== []) {
            $this->updateConfiguration($realm, $submitted);
        }

        if ($request->has('domain')) {
            $this->updateDomain->handle($realm, $request->string('domain')->toString());
            $this->checkDomain->handle($realm);
        }

        return $this->resource($realm->refresh());
    }

    /** The realm's configuration reads its settings rows. */
    private function resource(Realm $realm): RealmResource
    {
        return new RealmResource($realm->loadMissing('storedSettings'));
    }

    /**
     * The update validates the submission merged into the realm's current
     * configuration, so it reports a setting under its own name. On the wire
     * the settings live under `configuration`, and an error has to point there.
     *
     * @param  array<string, mixed>  $submitted
     */
    private function updateConfiguration(Realm $realm, array $submitted): void
    {
        try {
            $this->updateRealm->handle($realm, $submitted);
        } catch (ValidationException $exception) {
            $errors = [];

            foreach ($exception->errors() as $key => $messages) {
                $errors[$key === 'name' ? $key : 'configuration.'.$key] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
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
