<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\DeletesClient;
use App\Shared\Clients\Enums\ClientAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Purge\PurgeClient;
use Illuminate\Support\Facades\DB;

final readonly class DeleteClient implements DeletesClient
{
    public function __construct(private PurgeClient $purge) {}

    public function handle(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            ($this->purge)($client);

            Audit::record(ClientAuditEvent::ClientDeleted, $client, Realm::ofClient($client), [
                'realm' => $client->realm,
                'client_id' => $client->client_id,
                'name' => $client->name,
            ]);
        });
    }
}
