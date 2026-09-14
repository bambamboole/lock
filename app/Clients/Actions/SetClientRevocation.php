<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\SetsClientRevocation;
use App\Shared\Clients\Enums\ClientAdminEvent;
use Illuminate\Support\Facades\DB;
use Lock\Server\Clients\Models\Client;

final class SetClientRevocation implements SetsClientRevocation
{
    public function handle(Client $client, bool $revoked): void
    {
        if ($client->snapshot()->revoked === $revoked) {
            return;
        }

        DB::transaction(function () use ($client, $revoked): void {
            $client->forceFill(['revoked_at' => $revoked ? now() : null])->save();

            Audit::record($revoked ? ClientAdminEvent::ClientRevoked : ClientAdminEvent::ClientRestored, $client, Realm::ofClient($client), [
                'realm' => $client->realm,
                'client_id' => $client->client_id,
            ]);
        });
    }
}
