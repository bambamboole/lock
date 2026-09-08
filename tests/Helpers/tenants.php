<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;

/**
 * @return array{0: User, 1: Tenant}
 */
function tenantMember(RolePreset $role = RolePreset::Owner, bool $switch = true, ?Tenant $tenant = null): array
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->create();
    addMember($tenant, $user, $role);

    if ($switch) {
        $user->switchTenant($tenant);
    }

    $user->refresh();

    return [$user, $tenant];
}

function addMember(Tenant $tenant, User $user, RolePreset|Role $role = RolePreset::Member): void
{
    $role = $role instanceof Role ? $role : $tenant->roles()->where('preset', $role)->firstOrFail();

    $tenant->memberships()->create(['user_id' => $user->id]);

    $user->roles()->attach($role);
}
