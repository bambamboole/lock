<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesClient;
use App\Shared\Realms\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;

final readonly class CreateClient implements CreatesClient
{
    public function __construct(private ClientRepository $clients) {}

    /**
     * The repository stamps the current realm and its default scopes on the
     * client, so it runs with the target realm current.
     */
    public function handle(Realm $realm, ClientAttributes $attributes): Client
    {
        return DB::transaction(fn (): Client => $realm->runAsCurrent(function () use ($realm, $attributes): Client {
            $client = $attributes->usesAuthorizationCode()
                ? $this->clients->createAuthorizationCodeGrantClient($attributes->name, $attributes->redirectUris, $attributes->confidential())
                : $this->clients->createClientCredentialsGrantClient($attributes->name);

            $client->forceFill($attributes->toArray())->save();

            Audit::record(RealmAuditEvent::ClientCreated, $client, properties: [
                'realm' => $realm->slug,
                'client_id' => $client->client_id,
                'name' => $client->name,
            ]);

            return $client;
        }));
    }
}
