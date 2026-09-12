<?php
declare(strict_types=1);

namespace App\Shared\Clients\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Clients\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface CreatesClient
{
    /**
     * The returned client carries its plain secret in `plainSecret` when it is
     * confidential; the column only ever holds the hash.
     */
    public function handle(Realm $realm, ClientAttributes $attributes): Client;
}
