<?php
declare(strict_types=1);

namespace App\Shared\Clients\Contracts;

use App\Shared\Clients\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface UpdatesClient
{
    public function handle(Client $client, ClientAttributes $attributes): void;
}
