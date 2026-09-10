<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface SetsClientRevocation
{
    public function handle(Client $client, bool $revoked): void;
}
