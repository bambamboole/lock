<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use App\Shared\Auth\Contracts\DeletesUser;
use App\Shared\Auth\Contracts\EndsUserSessions;
use Bambamboole\LaravelOidc\Server\Purge\PurgeUser;
use Illuminate\Support\Facades\DB;

/**
 * Relying parties are told first, while the sessions still exist to build a
 * logout token from.
 */
final readonly class DeleteUser implements DeletesUser
{
    public function __construct(
        private EndsUserSessions $endSessions,
        private PurgeUser $purge,
    ) {}

    public function handle(User $user): void
    {
        $this->endSessions->handle($user);

        DB::transaction(function () use ($user): void {
            ($this->purge)($user);
            $user->notifications()->delete();
            $user->delete();
        });
    }
}
