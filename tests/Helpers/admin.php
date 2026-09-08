<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Tenants\Models\Role;

function globalAdmin(string $roleName = GlobalRoleSeeder::SUPER_ADMIN): User
{
    $admin = User::factory()->create();

    grantGlobalRole($admin, $roleName);

    return $admin;
}

function grantGlobalRole(User $user, string $name): void
{
    $user->roles()->attach(Role::query()->whereNull('tenant_id')->where('name', $name)->sole());
}
