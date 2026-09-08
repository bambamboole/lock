<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Shared\Audit\Audit;

final class CancelInvitation
{
    /**
     * The audit entry is written before the delete: it points at the invitation
     * as its subject, and the subject has to still exist when the row is stored.
     */
    public function handle(Realm $realm, RealmInvitation $invitation): void
    {
        Audit::record(RealmAuditEvent::InvitationCancelled, $invitation, $realm, [
            'email' => $invitation->email,
        ]);

        $invitation->delete();
    }
}
