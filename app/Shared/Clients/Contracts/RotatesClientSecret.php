<?php
declare(strict_types=1);

namespace App\Shared\Clients\Contracts;

use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

interface RotatesClientSecret
{
    /**
     * Returns the new plain secret; it is never readable again afterwards.
     */
    public function handle(Client $client): string;
}
