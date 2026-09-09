<?php
declare(strict_types=1);

namespace App\Realms\Http\Controllers;

use App\Realms\Http\Requests\AcceptRealmInvitationRequest;
use App\Realms\Models\RealmInvitation;
use App\Shared\Concerns\ResolvesCurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class AcceptInvitationController
{
    use ResolvesCurrentUser;

    /**
     * An invitation grants a role inside one realm, and a user only exists in
     * one realm, so accepting is only meaningful for a user already in it.
     * Inviting someone who has no account yet needs a registration surface that
     * names the realm, which arrives with the realm in the request path.
     */
    public function __invoke(AcceptRealmInvitationRequest $request, RealmInvitation $invitation): RedirectResponse
    {
        $user = $this->currentUser();

        abort_unless($user->belongsToRealm($invitation->realm), 403, __('realm.invitations.wrong-realm'));

        DB::transaction(function () use ($user, $invitation): void {
            if ($invitation->role_id !== null) {
                $user->roles()->syncWithoutDetaching([$invitation->role_id]);
            }

            $invitation->update(['accepted_at' => now()]);
        });

        return to_route('dashboard');
    }
}
