<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Resources\Models\ResourceScope;
use App\Roles\Models\Role;
use App\Shared\Api\ManagementApi;
use App\Shared\Api\ManagementRoles;
use App\Shared\Authorization\Enums\Scope;

function globalAdmin(string $roleName = ManagementRoles::SUPER_ADMIN): User
{
    $admin = User::factory()->for(Realm::master())->create();

    grantGlobalRole($admin, $roleName);

    return $admin;
}

function grantGlobalRole(User $user, string $name): void
{
    $user->roles()->attach(Realm::master()->roles()->where('name', $name)->sole());
}

/**
 * A throwaway master realm role holding exactly these management scopes.
 */
function globalAdminWith(Scope ...$scopes): User
{
    $realm = Realm::master();
    $admin = User::factory()->for($realm)->create();
    $role = Role::factory()->for($realm)->create();

    $role->scopes()->sync(managementScopeIds(...$scopes));
    $admin->roles()->attach($role);

    return $admin;
}

/**
 * @return list<string>
 */
function managementScopeIds(Scope ...$scopes): array
{
    $resource = Realm::master()->realmResources()->where('identifier', ManagementApi::RESOURCE)->sole();

    return array_values($resource->scopes()
        ->whereIn('value', array_map(fn (Scope $scope): string => $scope->value, $scopes))
        ->pluck('id')
        ->map(fn (mixed $id): string => (string) $id)
        ->all());
}

/**
 * @return list<string>
 */
function scopeValuesOf(Role $role): array
{
    return array_values($role->scopes()->pluck('value')->map(fn (mixed $value): string => (string) $value)->sort()->values()->all());
}

/**
 * @return list<string>
 */
function resourceScopeValues(ResourceScope ...$scopes): array
{
    return array_values(array_map(fn (ResourceScope $scope): string => $scope->value, $scopes));
}
