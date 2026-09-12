<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Resources\Models\Resource;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Api\ManagementRoles;
use Illuminate\Support\Facades\Gate;

use function Tests\Helpers\grantGlobalRole;
use function Tests\Helpers\managementScopeIds;

test('super admin passes every scope gate', function () {
    $user = User::factory()->for(Realm::master())->create();
    grantGlobalRole($user, ManagementRoles::SUPER_ADMIN);

    foreach (ApiScope::cases() as $scope) {
        expect(Gate::forUser($user)->allows($scope))->toBeTrue();
    }
});

test('support holds exactly the scopes its grant lists', function () {
    $user = User::factory()->for(Realm::master())->create();
    grantGlobalRole($user, ManagementRoles::SUPPORT);

    $granted = ManagementRoles::grants()[ManagementRoles::SUPPORT];

    foreach (ApiScope::cases() as $scope) {
        expect(Gate::forUser($user)->allows($scope))->toBe(in_array($scope, $granted, true));
    }
});

test('a write scope does not imply its read counterpart', function () {
    $user = User::factory()->for(Realm::master())->create();
    $role = RealmRole::factory()->for(Realm::master())->create();
    $role->scopes()->sync(managementScopeIds(ApiScope::RealmsWrite));
    $user->realmRoles()->attach($role);

    expect($user->can(ApiScope::RealmsWrite))->toBeTrue()
        ->and($user->can(ApiScope::RealmsRead))->toBeFalse();
});

test('a scope check reflects a role attached after an earlier check, once the user is refreshed', function () {
    $user = User::factory()->for(Realm::master())->create();
    expect($user->can(ApiScope::RealmsRead))->toBeFalse();

    grantGlobalRole($user, ManagementRoles::SUPER_ADMIN);

    expect($user->refresh()->can(ApiScope::RealmsRead))->toBeTrue();
});

test('a role outside the master realm grants nothing, whatever its scopes read', function () {
    $realm = Realm::factory()->create();
    $user = User::factory()->for($realm)->create();
    $role = RealmRole::factory()->for($realm)->create();
    $resource = Resource::factory()->for($realm)->create(['identifier' => 'api']);
    $scope = $resource->scopes()->create(['value' => ApiScope::RealmsWrite->value]);

    $role->scopes()->sync([$scope->id]);
    $user->realmRoles()->attach($role);

    foreach (ApiScope::cases() as $case) {
        expect(Gate::forUser($user)->allows($case))->toBeFalse();
    }
});
