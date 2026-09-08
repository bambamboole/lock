<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class CreateTenant
{
    public function handle(User $user, string $name, bool $isPersonal = false): Tenant
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $tenant = Tenant::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $ownerRole = $tenant->ownerRole();

            $tenant->memberships()->create(['user_id' => $user->id]);

            $user->roles()->attach($ownerRole);

            $user->switchTenant($tenant);

            return $tenant;
        });
    }
}
