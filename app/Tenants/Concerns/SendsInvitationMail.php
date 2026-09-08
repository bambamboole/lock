<?php
declare(strict_types=1);

namespace App\Tenants\Concerns;

use App\Auth\Models\User;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Notifications\TenantInvitation as TenantInvitationNotification;
use Illuminate\Support\Facades\Notification;

trait SendsInvitationMail
{
    private function sendInvitationMail(TenantInvitation $invitation, User $actor): void
    {
        Notification::route('mail', $invitation->email)
            ->notify(new TenantInvitationNotification($invitation)->locale($this->resolveInvitationLocale($invitation->email, $actor)));
    }

    /**
     * An invitee who already has an account reads it in their own language; a
     * stranger gets the inviter's, falling back to the app default.
     */
    private function resolveInvitationLocale(string $email, User $actor): string
    {
        $invitedUser = User::where('email', $email)->first();

        $locale = $invitedUser?->preferredLocale() ?? $actor->preferredLocale();

        return blank($locale) ? (string) config('app.locale') : $locale;
    }
}
