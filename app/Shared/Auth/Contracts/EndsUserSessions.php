<?php
declare(strict_types=1);

namespace App\Shared\Auth\Contracts;

use App\Auth\Models\User;

interface EndsUserSessions
{
    /**
     * Ends every active OIDC session of the user, or only the one named by
     * `$sid`, and returns how many were ended.
     */
    public function handle(User $user, ?string $sid = null): int;
}
