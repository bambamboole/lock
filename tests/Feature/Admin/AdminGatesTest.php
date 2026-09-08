<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use Illuminate\Support\Facades\Gate;

use function Tests\Helpers\grantGlobalRole;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('super admin passes every admin gate', function () {
    $user = User::factory()->create();
    grantGlobalRole($user, GlobalRoleSeeder::SUPER_ADMIN);

    foreach (AdminPermission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBeTrue();
    }
});

test('support admin can access and manage users but not realms or roles', function () {
    $user = User::factory()->create();
    grantGlobalRole($user, GlobalRoleSeeder::SUPPORT);

    expect(Gate::forUser($user)->allows(AdminPermission::Access))->toBeTrue()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageUsers))->toBeTrue()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageRealms))->toBeFalse()
        ->and(Gate::forUser($user)->allows(AdminPermission::ManageAdminRoles))->toBeFalse();
});

test('a non-admin fails every admin gate', function () {
    $user = User::factory()->create();

    foreach (AdminPermission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))->toBeFalse();
    }
});

test('an admin permission check reflects a role attached after an earlier check on the same instance', function () {
    $user = User::factory()->create();
    expect($user->can(AdminPermission::Access))->toBeFalse();

    grantGlobalRole($user, GlobalRoleSeeder::SUPER_ADMIN);

    expect($user->can(AdminPermission::Access))->toBeTrue();
});
