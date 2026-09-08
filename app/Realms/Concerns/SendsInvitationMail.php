<?php
declare(strict_types=1);

namespace App\Realms\Concerns;

use App\Auth\Models\User;
use App\Realms\Models\RealmInvitation;
use App\Realms\Notifications\RealmInvitation as RealmInvitationNotification;
use Illuminate\Support\Facades\Notification;

trait SendsInvitationMail
{
    private function sendInvitationMail(RealmInvitation $invitation, User $actor): void
    {
        Notification::route('mail', $invitation->email)
            ->notify(new RealmInvitationNotification($invitation)->locale($this->resolveInvitationLocale($invitation->email, $actor)));
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
