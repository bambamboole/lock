<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Auth\Models\User;
use App\Realms\Concerns\SendsInvitationMail;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Support\Audit;
use Illuminate\Validation\ValidationException;

final class ResendInvitation
{
    use SendsInvitationMail;

    public function handle(Realm $realm, User $actor, RealmInvitation $invitation): RealmInvitation
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages(['invitation' => [__('realm.accept.already-accepted')]]);
        }

        $invitation->update(['expires_at' => RealmInvitation::defaultExpiresAt()]);

        $this->sendInvitationMail($invitation, $actor);

        Audit::record(RealmAuditEvent::InvitationResent, $invitation, $realm, [
            'email' => $invitation->email,
        ]);

        return $invitation;
    }
}
