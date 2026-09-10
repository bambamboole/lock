<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\CreatesRealm;
use Illuminate\Support\Facades\DB;

final class CreateRealm implements CreatesRealm
{
    /**
     * Creates the realm with the default configuration. The realm starts
     * empty: its users register or are created inside it afterwards.
     */
    public function handle(string $name, ?string $slug = null): Realm
    {
        return DB::transaction(function () use ($name, $slug): Realm {
            $realm = Realm::create(array_filter([
                'name' => $name,
                'slug' => $slug,
            ]));

            Audit::record(RealmAuditEvent::RealmCreated, $realm);

            return $realm;
        });
    }
}
