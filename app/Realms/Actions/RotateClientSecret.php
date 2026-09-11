<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\RotatesClientSecret;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RotateClientSecret implements RotatesClientSecret
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(Client $client): string
    {
        if (! $client->confidential()) {
            throw new LogicException('A public client has no secret to rotate.');
        }

        return DB::transaction(function () use ($client): string {
            $this->clients->regenerateSecret($client);

            Audit::record(RealmAuditEvent::ClientSecretRotated, $client, Realm::ofClient($client), [
                'realm' => $client->realm_id,
                'client_id' => $client->client_id,
            ]);

            return $client->plainSecret ?? throw new LogicException('The regenerated secret is not readable.');
        });
    }
}
