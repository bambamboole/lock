<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\RealmRole;
use App\Resources\Models\ResourceScope;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealmRole;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class UpdateRealmRole implements UpdatesRealmRole
{
    /**
     * @param  list<string>  $scopeIds
     */
    public function handle(RealmRole $role, string $name, ?string $description, array $scopeIds): void
    {
        DB::transaction(function () use ($role, $name, $description, $scopeIds): void {
            $before = $role->only(['name', 'description']);
            $scopesBefore = $role->load('scopes')->scopeValues();

            $role->fill(['name' => $name, 'description' => $description])->save();
            $role->scopes()->sync($this->ownScopeIds($role, $scopeIds));

            $changes = [];

            foreach ($role->only(['name', 'description']) as $key => $value) {
                if ($before[$key] !== $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }

            $scopesAfter = $role->load('scopes')->scopeValues();

            if ($scopesBefore !== $scopesAfter) {
                $changes['scopes'] = ['old' => $scopesBefore, 'new' => $scopesAfter];
            }

            if ($changes !== []) {
                Audit::record(RealmAuditEvent::RoleUpdated, $role, $role->realm, [
                    'realm' => $role->realm->slug,
                    'name' => $role->name,
                    'changes' => $changes,
                ]);
            }
        });
    }

    /**
     * A role can only grant scopes of its own realm — anything else would let
     * one realm's console hand out another realm's access.
     *
     * @param  list<string>  $scopeIds
     * @return list<string>
     */
    private function ownScopeIds(RealmRole $role, array $scopeIds): array
    {
        if ($scopeIds === []) {
            return [];
        }

        return array_values(ResourceScope::query()
            ->whereIn('resource_scopes.id', $scopeIds)
            ->whereHas('resource', fn (Builder $query) => $query->where('realm_id', $role->realm_id))
            ->pluck('resource_scopes.id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all());
    }
}
