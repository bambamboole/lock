<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesClient;
use App\Shared\Realms\Enums\RealmAuditEvent;
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

            Audit::record(RealmAuditEvent::ClientDeleted, $client, Realm::ofClient($client), [
                'realm' => $client->realm_id,
                'client_id' => $client->client_id,
                'name' => $client->name,
            ]);
        });
    }
}
