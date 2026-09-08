<?php
declare(strict_types=1);

namespace App\Realms\Observers;

use App\Auth\Models\User;
use App\Realms\Models\RealmInvitation;
use Illuminate\Support\Str;
use Lattice\Notifications\Notification;

final class RealmInvitationObserver
{
    public function creating(RealmInvitation $invitation): void
    {
        if (empty($invitation->code)) {
            $invitation->code = Str::random(64);
        }
    }

    /**
     * The in-app notification only reaches an existing account; the invitation
     * mail (which also covers strangers) is the action's job.
     */
    public function created(RealmInvitation $invitation): void
    {
        $recipient = User::where('email', $invitation->email)->first();

        if ($recipient !== null) {
            $this->notifyRecipient($invitation, $recipient);
        }
    }

    private function notifyRecipient(RealmInvitation $invitation, User $recipient): void
    {
        if (! $recipient->settings->boolean('notifications.realm-invitations', true)) {
            return;
        }

        Notification::make()
            ->title(rt('realm:notification.invitation.title')->with(['realm' => $invitation->realm->name]))
            ->body(rt('realm:notification.invitation.body')->with([
                'inviter' => $invitation->inviter->name,
                'realm' => $invitation->realm->name,
            ]))
            ->icon('users')
            ->href(url("/invitations/{$invitation->code}/accept"))
            ->send($recipient);
    }
}
