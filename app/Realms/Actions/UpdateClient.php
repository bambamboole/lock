<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesClient;
use App\Shared\Realms\Data\ClientAttributes;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;

final readonly class UpdateClient implements UpdatesClient
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(Client $client, ClientAttributes $attributes): ?string
    {
        return DB::transaction(function () use ($client, $attributes): ?string {
            $before = $client->only(array_keys($attributes->toArray()));
            $wasConfidential = $client->confidential();

            $client->forceFill($attributes->toArray());

            if ($wasConfidential && ! $attributes->confidential()) {
                $client->secret = null;
            }

            $client->save();

            $secret = null;

            if (! $wasConfidential && $attributes->confidential()) {
                $this->clients->regenerateSecret($client);
                $secret = $client->plainSecret;
            }

            $changes = [];

            foreach ($client->only(array_keys($before)) as $key => $value) {
                if ($before[$key] != $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }

            if ($changes !== []) {
                Audit::record(RealmAuditEvent::ClientUpdated, $client, Realm::ofClient($client), [
                    'realm' => $client->realm_id,
                    'client_id' => $client->client_id,
                    'changes' => $changes,
                ]);
            }

            return $secret;
        });
    }
}
