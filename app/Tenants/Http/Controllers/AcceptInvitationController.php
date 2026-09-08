<?php
declare(strict_types=1);

namespace App\Tenants\Http\Controllers;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Http\Requests\AcceptTenantInvitationRequest;
use App\Tenants\Models\TenantInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class AcceptInvitationController
{
    use ResolvesCurrentUser;

    public function __invoke(AcceptTenantInvitationRequest $request, TenantInvitation $invitation): RedirectResponse
    {
        $user = $this->currentUser();

        DB::transaction(function () use ($user, $invitation): void {
            $tenant = $invitation->tenant;

            $tenant->memberships()->firstOrCreate(['user_id' => $user->id]);

            if ($invitation->role_id !== null) {
                $user->roles()->syncWithoutDetaching([$invitation->role_id]);
            }

            $invitation->update(['accepted_at' => now()]);

            $user->switchTenant($tenant);
        });

        return to_route('dashboard', ['current_tenant' => $invitation->tenant->slug]);
    }
}
