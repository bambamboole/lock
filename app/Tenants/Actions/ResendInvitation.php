<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Concerns\SendsInvitationMail;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Support\Audit;
use Illuminate\Validation\ValidationException;

final class ResendInvitation
{
    use SendsInvitationMail;

    public function handle(Tenant $tenant, User $actor, TenantInvitation $invitation): TenantInvitation
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages(['invitation' => [__('tenant.accept.already-accepted')]]);
        }

        $invitation->update(['expires_at' => TenantInvitation::defaultExpiresAt()]);

        $this->sendInvitationMail($invitation, $actor);

        Audit::record(TenantAuditEvent::InvitationResent, $invitation, $tenant, [
            'email' => $invitation->email,
        ]);

        return $invitation;
    }
}
