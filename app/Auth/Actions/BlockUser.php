<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\EndsUserSessions;
use App\Shared\Auth\Enums\UserAdminEvent;
use Illuminate\Support\Facades\DB;

/**
 * Ending the sessions on top of the block tells the relying parties now rather
 * than at their next token refresh.
 */
final readonly class BlockUser
{
    public function __construct(private EndsUserSessions $endSessions) {}

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill(['blocked_at' => now()])->save();

            Audit::record(UserAdminEvent::UserBlocked, $user);
        });

        $this->endSessions->handle($user);
    }
}
