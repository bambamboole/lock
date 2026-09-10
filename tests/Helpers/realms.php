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

/**
 * The configuration form payload for a realm as the form itself would submit
 * it: list settings joined line by line, overrides on top.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function realmFormData(Realm $realm, array $overrides = []): array
{
    $data = $realm->configuration();

    foreach (['allowed_redirect_schemes', 'allowed_redirect_domains', 'default_scopes', 'trusted_clients'] as $key) {
        $data[$key] = implode("\n", array_filter($realm->settings->array('realm.'.$key), is_string(...)));
    }

    return [...$data, ...$overrides];
}
