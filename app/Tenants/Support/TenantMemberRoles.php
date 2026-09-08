<?php
declare(strict_types=1);

namespace App\Tenants\Support;

use App\Auth\Models\User;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TenantMemberRoles
{
    /**
     * Members with their role labels, owner first and then alphabetically by name.
     *
     * @return Collection<int, array{id: string, name: string, email: string, role_labels: string, is_owner: bool}>
     */
    public function rows(Tenant $tenant, string $search = ''): Collection
    {
        $members = $tenant->members()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $inner) use ($search): void {
                    $inner->whereLike('users.name', "%{$search}%")
                        ->orWhereLike('users.email', "%{$search}%");
                });
            })
            ->get()
            ->load(['roles' => fn ($query) => $query->where('roles.tenant_id', $tenant->id)]);

        return $members
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role_labels' => $member->roles->map(fn (Role $role) => $role->displayName())->implode(', '),
                'is_owner' => $member->roles->contains(fn (Role $role) => $role->isOwner()),
            ])
            ->sortBy(fn (array $row) => [$row['is_owner'] ? 0 : 1, mb_strtolower($row['name'])])
            ->values();
    }
}
