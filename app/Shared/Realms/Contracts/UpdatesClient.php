<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Shared\Realms\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface UpdatesClient
{
    /**
     * Returns the freshly minted plain secret when the update turned a public
     * client confidential, null otherwise.
     */
    public function handle(Client $client, ClientAttributes $attributes): ?string;
}
