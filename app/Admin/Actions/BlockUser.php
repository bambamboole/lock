<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\EndsUserSessions;
use Illuminate\Support\Facades\DB;

/**
 * Setting `blocked_at` is what refuses the user everywhere (see
 * RealmUserProvider); ending the sessions on top tells the relying parties
 * instead of leaving them to find out at the next token refresh.
 */
final readonly class BlockUser
{
    public function __construct(private EndsUserSessions $endSessions) {}

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill(['blocked_at' => now()])->save();

            Audit::record(AdminAuditEvent::UserBlocked, $user);
        });

        $this->endSessions->handle($user);
    }
}
