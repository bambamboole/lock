<?php
declare(strict_types=1);

namespace App\Tenants\Notifications;

use App\Tenants\Models\TenantInvitation as TenantInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TenantInvitationModel $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invitation->tenant;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__('tenant.notification.subject', ['tenant' => $tenant->name]))
            ->line(__('tenant.notification.line', [
                'inviter' => $inviter->name,
                'tenant' => $tenant->name,
            ]))
            ->action(__('tenant.notification.action'), url("/invitations/{$this->invitation->code}/accept"));
    }
}
