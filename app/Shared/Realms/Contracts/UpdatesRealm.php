<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Realms\Models\Realm;
use App\Shared\Realms\Data\UpdateRealmData;
use Illuminate\Validation\ValidationException;

interface UpdatesRealm
{
    /**
     * Applies everything the submission carried — the realm's name, any subset
     * of its settings, a new domain — and leaves the rest as it stands. The
     * domain check is the caller's, since it leaves the process.
     *
     * @throws ValidationException
     */
    public function handle(Realm $realm, UpdateRealmData $data): Realm;
}
