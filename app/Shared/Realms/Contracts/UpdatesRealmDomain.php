<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use Illuminate\Validation\ValidationException;

interface UpdatesRealmDomain
{
    /**
     * Moves the realm to another host, which changes its issuer. The new
     * domain starts unchecked.
     *
     * @throws ValidationException
     */
    public function handle(Realm $realm, string $domain): void;
}
