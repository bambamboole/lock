<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Support\Audit;

final class CancelInvitation
{
    /**
     * The audit entry is written before the delete: it points at the invitation
     * as its subject, and the subject has to still exist when the row is stored.
     */
    public function handle(Tenant $tenant, TenantInvitation $invitation): void
    {
        Audit::record(TenantAuditEvent::InvitationCancelled, $invitation, $tenant, [
            'email' => $invitation->email,
        ]);

        $invitation->delete();
    }
}
