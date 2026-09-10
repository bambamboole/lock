<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Models\Role;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

/**
 * Instance administrators live in the master realm — that is what makes them
 * administrators of the others rather than members of their own.
 */
function globalAdmin(string $roleName = GlobalRoleSeeder::SUPER_ADMIN): User
{
    $admin = User::factory()->for(Realm::master())->create();

    grantGlobalRole($admin, $roleName);

    return $admin;
}

function grantGlobalRole(User $user, string $name): void
{
    $user->roles()->attach(Role::query()->where('name', $name)->sole());
}
