<?php
declare(strict_types=1);

namespace App\Roles\Actions;

use App\Realms\Models\Realm;
use App\Resources\Models\ResourceScope;
use App\Roles\Models\Role;
use App\Shared\Audit\Audit;
use App\Shared\Roles\Contracts\CreatesRole;
use App\Shared\Roles\Enums\RoleAuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class CreateRole implements CreatesRole
{
    /**
     * @param  list<string>  $scopeIds
     */
    public function handle(Realm $realm, string $name, ?string $description, array $scopeIds): Role
    {
        return DB::transaction(function () use ($realm, $name, $description, $scopeIds): Role {
            $role = $realm->roles()->create([
                'name' => $name,
                'description' => $description,
            ]);

            $role->scopes()->sync($this->ownScopeIds($realm, $scopeIds));

            Audit::record(RoleAuditEvent::RoleCreated, $role, $realm, [
                'realm' => $realm->slug,
                'name' => $role->name,
                'scopes' => $role->load('scopes')->scopeValues(),
            ]);

            return $role;
        });
    }

    /**
     * A role can only grant scopes of its own realm — anything else would let
     * one realm's console hand out another realm's access.
     *
     * @param  list<string>  $scopeIds
     * @return list<string>
     */
    private function ownScopeIds(Realm $realm, array $scopeIds): array
    {
        if ($scopeIds === []) {
            return [];
        }

        return array_values(ResourceScope::query()
            ->whereIn('resource_scopes.id', $scopeIds)
            ->whereHas('resource', fn (Builder $query) => $query->where('realm_id', $realm->id))
            ->pluck('resource_scopes.id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all());
    }
}
