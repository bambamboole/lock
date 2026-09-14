<?php
declare(strict_types=1);

namespace App\Clients\Http\Api\V1\Controllers;

use App\Clients\Http\Api\V1\Resources\ClientData;
use App\Realms\Models\Realm;
use App\Shared\Clients\Contracts\CreatesClient;
use App\Shared\Clients\Contracts\DeletesClient;
use App\Shared\Clients\Contracts\UpdatesClient;
use App\Shared\Clients\Data\CreateClientData;
use App\Shared\Clients\Data\UpdateClientData;
use Bambamboole\Spectacular\QueryBuilder;
use Dedoc\Scramble\Attributes\IgnoreResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Builder;
use Lock\Server\Clients\Models\Client;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * A client is only reachable through the realm it belongs to: `client_id` is
 * unique per realm, not across the instance.
 */
final readonly class ClientController
{
    public function __construct(
        private CreatesClient $createClient,
        private UpdatesClient $updateClient,
        private DeletesClient $deleteClient,
    ) {}

    /**
     * List the clients of a realm
     *
     * @return PaginatedDataCollection<array-key, ClientData>
     */
    public function index(Realm $realm): PaginatedDataCollection
    {
        $clients = QueryBuilder::for($this->clients($realm))
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::exact('token_endpoint_auth_method'),
                AllowedFilter::callback('revoked', fn (Builder $query, mixed $value): Builder => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? $query->whereNotNull('revoked_at')
                    : $query->whereNull('revoked_at')),
            )
            ->allowedSorts('name', 'client_id', 'created_at')
            ->defaultSort('name')
            ->apiPaginate();

        return ClientData::collect($clients, PaginatedDataCollection::class);
    }

    /**
     * Show a client
     */
    public function show(Realm $realm, string $client): ClientData
    {
        return ClientData::fromClient($this->client($realm, $client));
    }

    /**
     * Create a client
     *
     * A confidential client is minted a secret, which this is the response that
     * carries it. The scopes it is granted come from the realm's client policy.
     */
    #[IgnoreResponse(200)]
    #[Response(201, type: ClientData::class)]
    public function store(CreateClientData $data, Realm $realm): SymfonyResponse
    {
        $client = $this->createClient->handle($realm, $data->toClientAttributes());

        return ClientData::fromClient($client)->toResponse(request())->setStatusCode(SymfonyResponse::HTTP_CREATED);
    }

    /**
     * Update a client
     *
     * A client's `client_id` is immutable, and everything left out of the
     * payload keeps its current value. Turning a public client confidential
     * mints a secret; the other way around drops it.
     */
    public function update(UpdateClientData $data, Realm $realm, string $client): ClientData
    {
        $client = $this->client($realm, $client);

        $this->updateClient->handle($client, $data->toClientAttributes($client));

        return ClientData::fromClient($client->refresh());
    }

    /**
     * Delete a client
     *
     * Purges the client together with the tokens, grants and consents issued
     * for it. Blocking it instead keeps the trail.
     */
    public function destroy(Realm $realm, string $client): SymfonyResponse
    {
        $this->deleteClient->handle($this->client($realm, $client));

        return response()->noContent();
    }

    private function client(Realm $realm, string $clientId): Client
    {
        return $this->clients($realm)->where('client_id', $clientId)->firstOrFail();
    }

    /**
     * @return Builder<Client>
     */
    private function clients(Realm $realm): Builder
    {
        return Client::query()->where('realm', $realm->slug);
    }
}
