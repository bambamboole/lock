<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use Illuminate\Support\Facades\DB;

final class UnblockUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill(['blocked_at' => null])->save();

            Audit::record(AdminAuditEvent::UserUnblocked, $user);
        });
    }
}
