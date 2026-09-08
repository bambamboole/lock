<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Role;

test('a member gets the union of permissions from all their realm roles', function () {
    $user = User::factory()->create();
    $realm = $user->realm;

    $roleManager = Role::factory()->create([
        'realm_id' => $realm->id,
        'name' => 'Role manager',
    ]);
    $roleManager->syncPermissions([RealmPermission::ManageRoles->value]);

    $auditor = Role::factory()->create([
        'realm_id' => $realm->id,
        'name' => 'Auditor',
    ]);
    $auditor->syncPermissions([RealmPermission::ViewAudit->value]);

    $member = User::factory()->for($realm)->create();
    $member->roles()->attach([$roleManager->id, $auditor->id]);

    expect($member->hasRealmPermission($realm, RealmPermission::ManageRoles))->toBeTrue()
        ->and($member->hasRealmPermission($realm, RealmPermission::ViewAudit))->toBeTrue()
        ->and($member->hasRealmPermission($realm, RealmPermission::DeleteRealm))->toBeFalse();
});
