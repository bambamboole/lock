<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\Permission;
use App\Auth\Support\OidcScopeCatalog;
use App\Auth\Support\PermissionDefinition;
use App\Auth\Support\PermissionRepository;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use Illuminate\Support\Facades\Cache;

$values = fn (array $definitions): array => array_map(
    fn (PermissionDefinition $permission): string => $permission->value,
    $definitions,
);

it('discovers every tagged permission enum without a sync command', function () use ($values) {
    $repository = new PermissionRepository;

    expect($values($repository->tenant()))
        ->toEqualCanonicalizing(array_column(TenantPermission::cases(), 'value'))
        ->and($values($repository->global()))
        ->toEqualCanonicalizing(array_column(AdminPermission::cases(), 'value'));
});

it('publishes only scope-tagged tenant permissions, never global ones', function () {
    $scopes = (new PermissionRepository)->apiScopes();

    expect(array_keys($scopes))->toEqualCanonicalizing([
        TenantPermission::ViewMembers->value,
        TenantPermission::ManageMembers->value,
    ])->and($scopes['member:view'])->toBe(__('tenant.permissions.member.view'));
});

it('serves the oidc scope catalog straight from the permission catalog', function () {
    $repository = new PermissionRepository;

    expect(new OidcScopeCatalog($repository)->scopes())->toBe($repository->apiScopes());
});

it('caches only scalars so a class rename cannot poison a deployed cache', function () {
    (new PermissionRepository)->values();

    $cached = Cache::get(PermissionRepository::CACHE_KEY);

    expect($cached)->toBeArray()->not->toBeEmpty();

    foreach ($cached as $row) {
        expect(array_filter($row, fn (mixed $value): bool => ! is_scalar($value)))->toBeEmpty();
    }
});

it('falls back to fresh discovery when a cached row no longer matches an enum case', function () {
    Cache::forever(PermissionRepository::CACHE_KEY, [[
        'enum' => TenantPermission::class,
        'case' => 'RenamedAway',
        'value' => 'ghost:permission',
        'group' => 'navigation.tenant-settings',
        'global' => false,
        'description' => 'tenant.permissions.ghost.permission',
        'scope' => true,
    ]]);

    expect((new PermissionRepository)->values())
        ->not->toContain('ghost:permission')
        ->toContain(TenantPermission::ViewAudit->value);
});

it('rejects an unknown permission and prunes rows that left the catalog', function () {
    $role = Role::factory()->create();
    $role->syncPermissions([TenantPermission::ViewAudit->value]);

    $ghost = Permission::query()->create(['key' => 'ghost:permission']);
    $role->permissions()->attach($ghost->id);

    expect(fn () => $role->syncPermissions(['not-a-permission']))->toThrow(InvalidArgumentException::class);

    $role->syncPermissions([TenantPermission::ViewAudit->value]);

    expect(Permission::query()->where('key', 'ghost:permission')->exists())->toBeFalse()
        ->and($role->permissions()->count())->toBe(1);
});
