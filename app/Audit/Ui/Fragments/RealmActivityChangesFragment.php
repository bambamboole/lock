<?php

declare(strict_types=1);

namespace App\Audit\Ui\Fragments;

use App\Realms\Models\Realm;
use App\Shared\Audit\Models\Activity;
use App\Shared\Authorization\Enums\Scope;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('admin.realm-activity-changes', can: Scope::ActivityRead)]
final class RealmActivityChangesFragment extends ActivityChangesFragment
{
    protected function activity(): ?Activity
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);

        return $realm instanceof Realm ? Activity::forRealm($realm)->find($this->contextInt('activity')) : null;
    }

    protected function emptyText(): string
    {
        return __('admin.realm-activity.detail.empty');
    }
}
