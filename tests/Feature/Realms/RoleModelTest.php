<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\Permission;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;

use function Tests\Helpers\realmMember;

it('seeds the three preset roles when a realm is created', function () {
    $realm = Realm::factory()->create();

    expect($realm->roles()->pluck('preset')->map(fn (RolePreset $preset) => $preset->value)->all())
        ->toEqualCanonicalizing(['owner', 'admin', 'member']);
});

it('seeds the admin matrix and an empty member set', function () {
    $realm = Realm::factory()->create();

    $admin = $realm->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $member = $realm->memberRole();
    $owner = $realm->ownerRole();

    expect($admin->permissionValues())->toEqualCanonicalizing([
        'realm:update', 'member:manage', 'audit:view',
    ])
        ->and($member->permissionValues())->toBeEmpty()
        ->and($owner->permissionValues())->toBeEmpty()
        ->and($owner->isOwner())->toBeTrue();
});

it('creates permission rows and attaches them when syncing permissions', function () {
    $realm = Realm::factory()->create();
    $role = Role::factory()->for($realm)->create(['name' => 'Support']);

    $role->syncPermissions(['audit:view', 'member:manage']);

    expect($role->permissionValues())->toEqualCanonicalizing(['audit:view', 'member:manage'])
        ->and(Permission::query()->whereIn('key', ['audit:view', 'member:manage'])->count())->toBe(2);
});

it('shares a permission row across roles instead of duplicating it', function () {
    $realm = Realm::factory()->create();
    $admin = $realm->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $support = Role::factory()->for($realm)->create(['name' => 'Support']);

    $support->syncPermissions(['audit:view']);

    $permission = Permission::query()->where('key', 'audit:view')->firstOrFail();

    expect(Permission::query()->where('key', 'audit:view')->count())->toBe(1)
        ->and($admin->permissions()->whereKey($permission->id)->exists())->toBeTrue()
        ->and($support->permissions()->whereKey($permission->id)->exists())->toBeTrue();
});

it('rejects unknown permission strings when syncing', function (bool $global) {
    $role = $global
        ? Role::factory()->global()->create(['name' => 'Platform Admin'])
        : Role::factory()->for(Realm::factory()->create())->create(['name' => 'Support']);

    $role->syncPermissions(['not-a-permission']);
})->with([true, false])->throws(InvalidArgumentException::class);

it('rejects admin permissions when syncing a realm role', function () {
    $realm = Realm::factory()->create();
    $role = Role::factory()->for($realm)->create(['name' => 'Support']);

    $role->syncPermissions([AdminPermission::Access->value]);
})->throws(InvalidArgumentException::class);

it('accepts admin permissions when syncing a global role', function () {
    $role = Role::factory()->global()->create(['name' => 'Platform Admin']);

    $role->syncPermissions([AdminPermission::Access->value]);

    expect($role->permissionValues())->toBe([AdminPermission::Access->value]);
});

it('scopes realm roles to the realm and excludes global rows', function () {
    $realm = Realm::factory()->create();
    Role::factory()->global()->create(['name' => 'Platform Admin']);

    expect($realm->roles()->count())->toBe(3)
        ->and($realm->assignableRoles()->pluck('preset')->filter()->map(fn (RolePreset $preset) => $preset->value)->all())->not->toContain('owner');
});

it('translates the preset label while the preset row keeps its seeded default name', function () {
    $realm = Realm::factory()->create();

    $admin = $realm->roles()->where('preset', RolePreset::Admin)->firstOrFail();

    expect($admin->displayName())->toBe(RolePreset::Admin->label());
});

it('shows the raw name once a preset role has been renamed', function () {
    $realm = Realm::factory()->create();

    $admin = $realm->roles()->where('preset', RolePreset::Admin)->firstOrFail();
    $admin->update(['name' => 'Team Lead']);

    expect($admin->displayName())->toBe('Team Lead');
});

it('scopes globalRoles to roles without a realm', function () {
    [$user, $realm] = realmMember();
    $global = Role::factory()->global()->create(['name' => 'Super Admin']);
    $user->roles()->attach($global);

    expect($user->globalRoles()->pluck('id')->all())->toBe([$global->id])
        ->and($user->rolesFor($realm)->pluck('realm_id')->unique()->all())->toBe([$realm->id]);
});
