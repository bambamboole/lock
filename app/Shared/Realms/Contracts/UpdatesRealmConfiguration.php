<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Realms\Data\UpdateRealmData;
use Illuminate\Validation\ValidationException;

interface UpdatesRealmConfiguration
{
    /**
     * Applies what the submission carried — the realm's name, any subset of its
     * settings — and leaves the rest as it stands.
     *
     * @throws ValidationException
     */
    public function handle(Realm $realm, UpdateRealmData $data): void;
}
