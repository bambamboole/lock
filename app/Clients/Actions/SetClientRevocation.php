<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\SetsClientRevocation;
use App\Shared\Clients\Enums\ClientAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;

final class SetClientRevocation implements SetsClientRevocation
{
    public function handle(Client $client, bool $revoked): void
    {
        if ($client->isRevoked() === $revoked) {
            return;
        }

        DB::transaction(function () use ($client, $revoked): void {
            $client->forceFill(['revoked_at' => $revoked ? now() : null])->save();

            Audit::record($revoked ? ClientAuditEvent::ClientRevoked : ClientAuditEvent::ClientRestored, $client, Realm::ofClient($client), [
                'realm' => $client->realm_id,
                'client_id' => $client->client_id,
            ]);
        });
    }
}
