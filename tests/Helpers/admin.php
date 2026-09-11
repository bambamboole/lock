<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

function globalAdmin(string $roleName = GlobalRoles::SUPER_ADMIN): User
{
    $admin = User::factory()->for(Realm::master())->create();

    grantGlobalRole($admin, $roleName);

    return $admin;
}

function grantGlobalRole(User $user, string $name): void
{
    $user->roles()->attach(Role::query()->where('name', $name)->sole());
}
