<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\UpdatesClient;
use App\Shared\Clients\Data\ClientAttributes;
use App\Shared\Clients\Enums\ClientAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;

final readonly class UpdateClient implements UpdatesClient
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(Client $client, ClientAttributes $attributes): void
    {
        DB::transaction(function () use ($client, $attributes): void {
            $before = $client->only(array_keys($attributes->toArray()));
            $wasConfidential = $client->confidential();

            $client->forceFill($attributes->toArray());

            if ($wasConfidential && ! $attributes->confidential()) {
                $client->secret = null;
            }

            $client->save();

            if (! $wasConfidential && $attributes->confidential()) {
                $this->clients->regenerateSecret($client);
            }

            $changes = [];

            foreach ($client->only(array_keys($before)) as $key => $value) {
                if ($before[$key] != $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }

            if ($changes !== []) {
                Audit::record(ClientAuditEvent::ClientUpdated, $client, Realm::ofClient($client), [
                    'realm' => $client->realm,
                    'client_id' => $client->client_id,
                    'changes' => $changes,
                ]);
            }
        });
    }
}
