<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesRealmRole;
use Illuminate\Support\Facades\DB;

final class CreateRealmRole implements CreatesRealmRole
{
    public function handle(Realm $realm, string $name, ?string $description): RealmRole
    {
        return DB::transaction(function () use ($realm, $name, $description): RealmRole {
            $role = $realm->realmRoles()->create([
                'name' => $name,
                'description' => $description,
            ]);

            Audit::record(RealmAuditEvent::RoleCreated, $role, properties: [
                'realm' => $realm->slug,
                'name' => $role->name,
            ]);

            return $role;
        });
    }
}
