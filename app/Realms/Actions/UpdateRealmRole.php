<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\RealmRole;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealmRole;
use Illuminate\Support\Facades\DB;

final class UpdateRealmRole implements UpdatesRealmRole
{
    public function handle(RealmRole $role, string $name, ?string $description): void
    {
        DB::transaction(function () use ($role, $name, $description): void {
            $before = $role->only(['name', 'description']);

            $role->fill(['name' => $name, 'description' => $description])->save();

            $changes = [];

            foreach ($role->only(['name', 'description']) as $key => $value) {
                if ($before[$key] !== $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }

            if ($changes !== []) {
                Audit::record(RealmAuditEvent::RoleUpdated, $role, properties: [
                    'realm' => $role->realm->slug,
                    'name' => $role->name,
                    'changes' => $changes,
                ]);
            }
        });
    }
}
