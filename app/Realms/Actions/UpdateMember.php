<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateMember
{
    /**
     * @param  array<int, string>  $roleIds
     */
    public function handle(Realm $realm, User $member, array $roleIds): void
    {
        abort_if($member->ownsRealm($realm), 403, __('realm.members.owner-role-cannot-be-changed'));

        $roleIds = array_values(array_unique(array_map(strval(...), $roleIds)));
        $roles = $realm->assignableRoles()->whereIn('id', $roleIds);

        if ($roles->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['role_ids' => [__('validation.exists', ['attribute' => 'role_ids'])]]);
        }

        DB::transaction(function () use ($realm, $member, $roles): void {
            $member->roles()->detach($realm->roles()->pluck('id'));
            $member->roles()->attach($roles->pluck('id')->all());
        });

        Audit::record(RealmAuditEvent::MemberRoleChanged, $member, $realm, [
            'member' => $member->name,
            'roles' => $roles->pluck('name')->all(),
        ]);
    }
}
