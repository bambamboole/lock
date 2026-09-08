<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use RuntimeException;

/**
 * A user inside a realm, holding one of its roles.
 *
 * @return array{0: User, 1: Realm}
 */
function realmMember(RolePreset $role = RolePreset::Owner, ?Realm $realm = null): array
{
    $realm ??= Realm::factory()->create();
    $user = User::factory()->for($realm)->create();

    addMember($realm, $user, $role);

    return [$user->refresh(), $realm];
}

/**
 * Grants a realm role to a user who already lives in that realm. Membership is
 * the user's realm_id, so there is nothing else to attach.
 */
function addMember(Realm $realm, User $user, RolePreset|Role $role = RolePreset::Member): void
{
    if (! $user->belongsToRealm($realm)) {
        throw new RuntimeException('Cannot grant a role in a realm the user does not belong to.');
    }

    $role = $role instanceof Role ? $role : $realm->roles()->where('preset', $role)->firstOrFail();

    $user->roles()->attach($role);
}
