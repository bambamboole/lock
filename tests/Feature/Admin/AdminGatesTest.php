<?php

declare(strict_types=1);

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Admin\Enums\AdminPermission;
use Illuminate\Support\Facades\Gate;

use function Tests\Helpers\grantGlobalRole;

test('super admin passes every admin gate', function () {
    $user = User::factory()->for(Realm::master())->create();
    grantGlobalRole($user, GlobalRoles::SUPER_ADMIN);

    foreach (AdminPermission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBeTrue();
    }
});

test('support admin can access and manage users but not realms or roles', function () {
    $user = User::factory()->for(Realm::master())->create();
    grantGlobalRole($user, GlobalRoles::SUPPORT);

    expect(Gate::forUser($user)->allows(AdminPermission::Access))->toBeTrue()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageUsers))->toBeTrue()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageRealms))->toBeFalse()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageAdminRoles))->toBeFalse();
});

test('an admin permission check reflects a role attached after an earlier check, once the user is refreshed', function () {
    $user = User::factory()->for(Realm::master())->create();
    expect($user->can(AdminPermission::Access))->toBeFalse();

    grantGlobalRole($user, GlobalRoles::SUPER_ADMIN);

    expect($user->refresh()->can(AdminPermission::Access))->toBeTrue();
});

test('a global role held outside the master realm grants nothing', function () {
    $user = User::factory()->create();
    grantGlobalRole($user, GlobalRoles::SUPER_ADMIN);

    foreach (AdminPermission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBeFalse();
    }
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
