<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\SetsClientRevocation;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Illuminate\Support\Facades\DB;

final class SetClientRevocation implements SetsClientRevocation
{
    public function handle(Client $client, bool $revoked): void
    {
        if ($client->revoked === $revoked) {
            return;
        }

        DB::transaction(function () use ($client, $revoked): void {
            $client->forceFill(['revoked' => $revoked])->save();

            Audit::record($revoked ? RealmAuditEvent::ClientRevoked : RealmAuditEvent::ClientRestored, $client, properties: [
                'realm' => $client->realm_id,
                'client_id' => $client->client_id,
            ]);
        });
    }
}
