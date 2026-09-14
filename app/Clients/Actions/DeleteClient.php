<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\DeletesClient;
use App\Shared\Clients\Enums\ClientAdminEvent;
use Illuminate\Support\Facades\DB;
use Lock\Server\Clients\Models\Client;

final readonly class DeleteClient implements DeletesClient
{
    public function handle(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            $client->delete();

            Audit::record(ClientAdminEvent::ClientDeleted, $client, Realm::ofClient($client), [
                'realm' => $client->realm,
                'client_id' => $client->client_id,
                'name' => $client->name,
            ]);
        });
    }
}
