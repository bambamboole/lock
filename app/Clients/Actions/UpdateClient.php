<?php
declare(strict_types=1);

namespace App\Clients\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Clients\Contracts\UpdatesClient;
use App\Shared\Clients\Data\ClientAttributes;
use App\Shared\Clients\Enums\ClientAdminEvent;
use App\Shared\Clients\Support\ClientIsConsistent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lock\Server\Clients\ClientRepository;
use Lock\Server\Clients\Models\Client;

final readonly class UpdateClient implements UpdatesClient
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(Client $client, ClientAttributes $attributes): void
    {
        $this->guardConsistency($attributes);

        DB::transaction(function () use ($client, $attributes): void {
            $before = $client->only(array_keys($attributes->toArray()));
            $wasConfidential = $client->snapshot()->confidential;

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
                Audit::record(ClientAdminEvent::ClientUpdated, $client, Realm::ofClient($client), [
                    'realm' => $client->realm,
                    'client_id' => $client->client_id,
                    'changes' => $changes,
                ]);
            }
        });
    }

    /**
     * The same three constraints {@see ClientIsConsistent}
     * states on the create payload. An update carries only the fields it
     * changes, so the merged client is the first place the answers exist and a
     * rule on the payload would be reading half of one.
     */
    private function guardConsistency(ClientAttributes $attributes): void
    {
        /** @var array<string, list<string>> $problems */
        $problems = [];

        if (in_array('refresh_token', $attributes->grantTypes, true) && ! $attributes->usesAuthorizationCode()) {
            $problems['grant_types'][] = __('admin.clients.validation.refresh-token-needs-code');
        }

        if (in_array('client_credentials', $attributes->grantTypes, true) && ! $attributes->confidential()) {
            $problems['grant_types'][] = __('admin.clients.validation.client-credentials-needs-secret');
        }

        if ($attributes->usesAuthorizationCode() && $attributes->redirectUris === []) {
            $problems['redirect_uris'][] = __('admin.clients.validation.redirect-uri-required');
        }

        if ($problems !== []) {
            throw ValidationException::withMessages($problems);
        }
    }
}
