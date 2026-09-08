<?php
declare(strict_types=1);

namespace App\Realms\Policies;

use App\Auth\Models\User;
use App\Auth\Support\PermissionRepository;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use Illuminate\Support\Str;

final readonly class RealmPolicy
{
    public function __construct(private PermissionRepository $permissions) {}

    public function view(User $user, Realm $realm): bool
    {
        return $user->belongsToRealm($realm);
    }

    public function delete(User $user, Realm $realm): bool
    {
        return ! $realm->isMaster() && $user->hasRealmPermission($realm, RealmPermission::DeleteRealm);
    }

    /**
     * Resolves every realm-scoped permission ability (`audit:view`, …) checked with a
     * Realm subject. Colon-containing abilities never match a real method, so PHP routes
     * them here; the real methods above stay untouched. The Gate camel-cases hyphenated
     * abilities before calling the policy (a `sales-price:manage` would arrive as
     * `salesPrice:manage`), so the lookup falls back to the kebab-cased form.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $ability, array $arguments): bool
    {
        [$user, $realm] = $arguments + [null, null];

        if (! $user instanceof User || ! $realm instanceof Realm) {
            return false;
        }

        $permission = $this->permissions->find($ability) ?? $this->permissions->find(Str::kebab($ability));

        return $permission !== null
            && ! $permission->global
            && $user->hasRealmPermission($realm, $permission->case);
    }
}
