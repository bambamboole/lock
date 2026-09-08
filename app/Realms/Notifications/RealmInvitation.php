<?php
declare(strict_types=1);

namespace App\Realms\Notifications;

use App\Realms\Models\RealmInvitation as RealmInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RealmInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RealmInvitationModel $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $realm = $this->invitation->realm;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__('realm.notification.subject', ['realm' => $realm->name]))
            ->line(__('realm.notification.line', [
                'inviter' => $inviter->name,
                'realm' => $realm->name,
            ]))
            ->action(__('realm.notification.action'), url("/invitations/{$this->invitation->code}/accept"));
    }
}
