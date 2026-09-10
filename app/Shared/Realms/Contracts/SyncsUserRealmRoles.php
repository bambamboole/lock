<?php
declare(strict_types=1);

namespace App\Shared\Realms\Contracts;

use App\Auth\Models\User;

interface SyncsUserRealmRoles
{
    /**
     * Every id has to name a role of the user's own realm; anything else is rejected.
     *
     * @param  list<string>  $roleIds
     */
    public function handle(User $user, array $roleIds): void;
}
