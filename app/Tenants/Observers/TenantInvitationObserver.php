<?php
declare(strict_types=1);

namespace App\Tenants\Observers;

use App\Auth\Models\User;
use App\Tenants\Models\TenantInvitation;
use Illuminate\Support\Str;
use Lattice\Notifications\Notification;

final class TenantInvitationObserver
{
    public function creating(TenantInvitation $invitation): void
    {
        if (empty($invitation->code)) {
            $invitation->code = Str::random(64);
        }
    }

    /**
     * The in-app notification only reaches an existing account; the invitation
     * mail (which also covers strangers) is the action's job.
     */
    public function created(TenantInvitation $invitation): void
    {
        $recipient = User::where('email', $invitation->email)->first();

        if ($recipient !== null) {
            $this->notifyRecipient($invitation, $recipient);
        }
    }

    private function notifyRecipient(TenantInvitation $invitation, User $recipient): void
    {
        if (! $recipient->settings->boolean('notifications.tenant-invitations', true)) {
            return;
        }

        Notification::make()
            ->title(rt('tenant:notification.invitation.title')->with(['tenant' => $invitation->tenant->name]))
            ->body(rt('tenant:notification.invitation.body')->with([
                'inviter' => $invitation->inviter->name,
                'tenant' => $invitation->tenant->name,
            ]))
            ->icon('users')
            ->href(url("/invitations/{$invitation->code}/accept"))
            ->send($recipient);
    }
}
