<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Support\Audit;
use Illuminate\Support\Facades\DB;

final class RemoveMember
{
    public function handle(Realm $realm, User $member): void
    {
        abort_if($realm->owner()?->is($member) === true, 403, __('realm.members.owner-cannot-be-removed'));

        // Identity is realm-local: there is no account left over once the
        // user leaves the realm, so removal is deletion. The audit entry is
        // written first, while the member still resolves.
        Audit::record(RealmAuditEvent::MemberRemoved, $member, $realm, [
            'member' => $member->name,
        ]);

        DB::transaction(function () use ($realm, $member): void {
            $member->roles()->detach($realm->roles()->pluck('id'));
            $member->delete();
        });
    }
}
