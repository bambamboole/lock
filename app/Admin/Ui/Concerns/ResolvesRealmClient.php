<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

/**
 * A client is only reachable through the realm it was created in: the
 * `client` context alone would let a signed reference from one realm's
 * console address another realm's client.
 */
trait ResolvesRealmClient
{
    private function realm(): Realm
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return $realm;
    }

    private function realmClient(): Client
    {
        $client = $this->realmClientOrNull();

        abort_unless($client instanceof Client, 404);

        return $client;
    }

    private function realmClientOrNull(): ?Client
    {
        $realm = $this->contextModelOrNull('realm');
        $client = $this->contextModelOrNull('client');

        return $realm instanceof Realm && $client instanceof Client && $client->realm_id === $realm->slug ? $client : null;
    }
}
