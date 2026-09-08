<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Concerns\SendsInvitationMail;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InviteMember
{
    use SendsInvitationMail;

    public function handle(Tenant $tenant, User $inviter, string $email, ?string $roleId = null): TenantInvitation
    {
        if ($tenant->hasMemberOrPendingInvitation($email)) {
            throw ValidationException::withMessages(['email' => [__('tenant.invite.already-member')]]);
        }

        $role = $this->resolveRole($tenant, $roleId);

        $invitation = DB::transaction(fn (): TenantInvitation => $tenant->invitations()->create([
            'email' => $email,
            'role_id' => $role->id,
            'invited_by' => $inviter->id,
            'expires_at' => TenantInvitation::defaultExpiresAt(),
        ]));

        $this->sendInvitationMail($invitation, $inviter);

        Audit::record(TenantAuditEvent::InvitationCreated, $invitation, $tenant, [
            'email' => $invitation->email,
            'role' => $role->name,
        ]);

        return $invitation;
    }

    private function resolveRole(Tenant $tenant, ?string $roleId): Role
    {
        if ($roleId === null) {
            return $tenant->memberRole();
        }

        $role = $tenant->roles()
            ->whereKey($roleId)
            ->where(fn ($query) => $query->whereNull('preset')->orWhere('preset', '!=', RolePreset::Owner))
            ->first();

        if (! $role instanceof Role) {
            throw ValidationException::withMessages(['role_id' => [__('validation.exists', ['attribute' => 'role_id'])]]);
        }

        return $role;
    }
}
