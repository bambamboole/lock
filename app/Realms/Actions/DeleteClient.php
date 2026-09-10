<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\DeletesClient;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Consents\Models\Consent;
use Bambamboole\LaravelOidc\Server\Sessions\Models\SessionParticipant;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AccessToken;
use Bambamboole\LaravelOidc\Server\Tokens\Models\AuthorizationCode;
use Bambamboole\LaravelOidc\Server\Tokens\Models\RefreshToken;
use Illuminate\Support\Facades\DB;

final class DeleteClient implements DeletesClient
{
    /**
     * The package tables reference the client without a foreign key, so
     * everything issued to it is removed here explicitly.
     */
    public function handle(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            RefreshToken::query()
                ->whereIn('access_token_id', AccessToken::query()->where('client_id', $client->id)->select('id'))
                ->delete();
            AccessToken::query()->where('client_id', $client->id)->delete();
            AuthorizationCode::query()->where('client_id', $client->id)->delete();
            Consent::query()->where('client_id', $client->id)->delete();
            SessionParticipant::query()->where('client_id', $client->id)->delete();

            $client->delete();

            Audit::record(RealmAuditEvent::ClientDeleted, $client, properties: [
                'realm' => $client->realm_id,
                'client_id' => $client->client_id,
                'name' => $client->name,
            ]);
        });
    }
}
