<?php

declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Permission;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;

use function Tests\Helpers\addMember;

test('a user with a global role granting admin:access is an admin', function () {
    $user = User::factory()->for(Realm::master())->create();
    $role = Role::factory()->global()->create(['name' => 'Super Admin']);
    $role->syncPermissions([
        AdminPermission::Access->value,
        AdminPermission::ManageUsers->value,
    ]);
    $user->roles()->attach($role);

    expect($user->can(AdminPermission::Access))->toBeTrue()
        ->and($user->can(AdminPermission::ManageUsers))->toBeTrue()
        ->and($user->can(AdminPermission::ManageRealms))->toBeFalse();
});

test('a realm role cannot be given a global permission', function () {
    $realm = Realm::factory()->create();
    $realmRole = Role::factory()->for($realm)->create(['name' => 'Support']);

    $realmRole->syncPermissions([AdminPermission::Access->value]);
})->throws(InvalidArgumentException::class);

test('a global permission smuggled onto a realm role grants nothing', function () {
    $realm = Realm::factory()->create();
    $realmRole = Role::factory()->for($realm)->create(['name' => 'Support']);
    $realmRole->permissions()->attach(
        Permission::query()->create(['key' => AdminPermission::Access->value])->id,
    );

    $user = User::factory()->for($realm)->create();
    addMember($realm, $user, $realmRole);

    expect($user->can(AdminPermission::Access))->toBeFalse()
        ->and($user->can(AdminPermission::Access))->toBeFalse()
        ->and($user->can(AdminPermission::Access, $realm))->toBeFalse()
        ->and($user->hasRealmPermission($realm, AdminPermission::Access))->toBeFalse();
});

test('a realm owner reaches every realm permission but no global one', function () {
    $realm = Realm::factory()->create();
    $owner = User::factory()->for($realm)->create();
    addMember($realm, $owner, $realm->ownerRole());

    expect($owner->can(RealmPermission::ManageRoles, $realm))->toBeTrue()
        ->and($owner->can(AdminPermission::ManageUsers, $realm))->toBeFalse()
        ->and($owner->can(AdminPermission::Access))->toBeFalse();
});

test('a granted :manage permission satisfies its :view counterpart', function () {
    $realm = Realm::factory()->create();
    $role = Role::factory()->for($realm)->create(['name' => 'Role admin']);
    $role->syncPermissions([RealmPermission::ManageRoles->value]);

    $user = User::factory()->for($realm)->create();
    addMember($realm, $user, $role);

    expect($user->can(RealmPermission::ManageRoles, $realm))->toBeTrue()
        ->and($user->can(RealmPermission::ViewAudit, $realm))->toBeFalse();
});
