<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Concerns\SendsInvitationMail;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Models\Role;
use App\Realms\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InviteMember
{
    use SendsInvitationMail;

    public function handle(Realm $realm, User $inviter, string $email, ?string $roleId = null): RealmInvitation
    {
        if ($realm->hasMemberOrPendingInvitation($email)) {
            throw ValidationException::withMessages(['email' => [__('realm.invite.already-member')]]);
        }

        $role = $this->resolveRole($realm, $roleId);

        $invitation = DB::transaction(fn (): RealmInvitation => $realm->invitations()->create([
            'email' => $email,
            'role_id' => $role->id,
            'invited_by' => $inviter->id,
            'expires_at' => RealmInvitation::defaultExpiresAt(),
        ]));

        $this->sendInvitationMail($invitation, $inviter);

        Audit::record(RealmAuditEvent::InvitationCreated, $invitation, $realm, [
            'email' => $invitation->email,
            'role' => $role->name,
        ]);

        return $invitation;
    }

    private function resolveRole(Realm $realm, ?string $roleId): Role
    {
        if ($roleId === null) {
            return $realm->memberRole();
        }

        $role = $realm->roles()
            ->whereKey($roleId)
            ->where(fn ($query) => $query->whereNull('preset')->orWhere('preset', '!=', RolePreset::Owner))
            ->first();

        if (! $role instanceof Role) {
            throw ValidationException::withMessages(['role_id' => [__('validation.exists', ['attribute' => 'role_id'])]]);
        }

        return $role;
    }
}
