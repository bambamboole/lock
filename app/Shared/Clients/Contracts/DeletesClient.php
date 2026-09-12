<?php
declare(strict_types=1);

namespace App\Shared\Clients\Contracts;

use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface DeletesClient
{
    public function handle(Client $client): void;
}
