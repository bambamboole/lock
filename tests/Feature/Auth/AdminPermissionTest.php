<?php

declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Admin\Models\Role;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

test('a user with a global role granting admin:access is an admin', function () {
    $user = User::factory()->for(Realm::master())->create();
    $role = Role::factory()->create(['name' => 'Super Admin']);
    $role->syncPermissions([
        AdminPermission::Access,
        AdminPermission::ManageUsers,
    ]);
    $user->roles()->attach($role);

    expect($user->can(AdminPermission::Access))->toBeTrue()
        ->and($user->can(AdminPermission::ManageUsers))->toBeTrue()
        ->and($user->can(AdminPermission::ManageRealms))->toBeFalse();
});

test('a granted :manage permission satisfies its :view counterpart', function () {
    $user = User::factory()->for(Realm::master())->create();
    $role = Role::factory()->create(['name' => 'Realm admin']);
    $role->syncPermissions([AdminPermission::ManageRealms]);
    $user->roles()->attach($role);

    expect($user->can(AdminPermission::ManageRealms))->toBeTrue()
        ->and($user->can(AdminPermission::ViewRealms))->toBeTrue()
        ->and($user->can(AdminPermission::ManageUsers))->toBeFalse();
});
