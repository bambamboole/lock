<?php
declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use Illuminate\Support\Facades\DB;

final class UpdateRealmUser
{
    public function handle(User $user, string $name, string $email, bool $verified): void
    {
        DB::transaction(function () use ($user, $name, $email, $verified): void {
            $before = ['name' => $user->name, 'email' => $user->email, 'verified' => $user->hasVerifiedEmail()];

            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $verified ? ($user->email_verified_at ?? now()) : null,
            ])->save();

            $after = ['name' => $user->name, 'email' => $user->email, 'verified' => $user->hasVerifiedEmail()];
            $changed = array_keys(array_filter($after, fn (mixed $value, string $key): bool => $value !== $before[$key], ARRAY_FILTER_USE_BOTH));

            if ($changed === []) {
                return;
            }

            Audit::record(AdminAuditEvent::UserUpdated, $user, properties: [
                'before' => array_intersect_key($before, array_flip($changed)),
                'after' => array_intersect_key($after, array_flip($changed)),
            ]);
        });
    }
}
