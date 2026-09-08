<?php
declare(strict_types=1);

namespace App\Tenants\Policies;

use App\Auth\Models\User;
use App\Auth\Support\PermissionRepository;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Str;

final readonly class TenantPolicy
{
    public function __construct(private PermissionRepository $permissions) {}

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return ! $tenant->is_personal && $user->hasTenantPermission($tenant, TenantPermission::DeleteTenant);
    }

    /**
     * Resolves every tenant-scoped permission ability (`audit:view`, …) checked with a
     * Tenant subject. Colon-containing abilities never match a real method, so PHP routes
     * them here; the real methods above stay untouched. The Gate camel-cases hyphenated
     * abilities before calling the policy (a `sales-price:manage` would arrive as
     * `salesPrice:manage`), so the lookup falls back to the kebab-cased form.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $ability, array $arguments): bool
    {
        [$user, $tenant] = $arguments + [null, null];

        if (! $user instanceof User || ! $tenant instanceof Tenant) {
            return false;
        }

        $permission = $this->permissions->find($ability) ?? $this->permissions->find(Str::kebab($ability));

        return $permission !== null
            && ! $permission->global
            && $user->hasTenantPermission($tenant, $permission->case);
    }
}
