<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;

/**
 * A role is only reachable through its own realm: the `realmRole` context
 * alone would let a signed reference from one realm's console address
 * another realm's role.
 */
trait ResolvesRealmRole
{
    private function realm(): Realm
    {
        return $this->contextModel('realm', Realm::class);
    }

    private function realmRole(): RealmRole
    {
        $role = $this->realmRoleOrNull();

        abort_unless($role instanceof RealmRole, 404);

        return $role;
    }

    private function realmRoleOrNull(): ?RealmRole
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);
        $role = $this->contextModelOrNull('realmRole', RealmRole::class);

        return $realm instanceof Realm && $role instanceof RealmRole && $role->belongsToRealm($realm) ? $role : null;
    }
}
