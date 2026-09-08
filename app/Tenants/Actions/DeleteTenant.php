<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class DeleteTenant
{
    public function handle(User $user, Tenant $tenant): void
    {
        $fallbackTenant = $user->isCurrentTenant($tenant)
            ? $user->fallbackTenant($tenant)
            : null;

        DB::transaction(function () use ($user, $tenant): void {
            User::where('current_tenant_id', $tenant->id)
                ->where('id', '!=', $user->id)
                ->each(function (User $affectedUser): void {
                    $personalTenant = $affectedUser->personalTenant();

                    if ($personalTenant instanceof Tenant) {
                        $affectedUser->switchTenant($personalTenant);
                    }
                });

            $tenant->invitations()->delete();
            $tenant->memberships()->delete();
            $tenant->delete();
        });

        if ($fallbackTenant instanceof Tenant) {
            $user->switchTenant($fallbackTenant);
        }
    }
}
