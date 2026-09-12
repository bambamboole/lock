<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\RotatesClientSecret;
use App\Shared\Clients\Enums\ClientAdminEvent;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RotateClientSecret implements RotatesClientSecret
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(Client $client): void
    {
        if (! $client->confidential()) {
            throw new LogicException('A public client has no secret to rotate.');
        }

        DB::transaction(function () use ($client): void {
            $this->clients->regenerateSecret($client);

            Audit::record(ClientAdminEvent::ClientSecretRotated, $client, Realm::ofClient($client), [
                'realm' => $client->realm,
                'client_id' => $client->client_id,
            ]);
        });
    }
}
