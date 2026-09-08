<?php
declare(strict_types=1);

namespace App\Realms\Support;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Shared\Realms\Contracts\RealmMemberDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class RealmMemberRoles implements RealmMemberDirectory
{
    public function rows(Realm $realm, string $search = ''): Collection
    {
        $members = $realm->users()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $inner) use ($search): void {
                    $inner->whereLike('users.name', "%{$search}%")
                        ->orWhereLike('users.email', "%{$search}%");
                });
            })
            ->get()
            ->load(['roles' => fn ($query) => $query->where('roles.realm_id', $realm->id)]);

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
