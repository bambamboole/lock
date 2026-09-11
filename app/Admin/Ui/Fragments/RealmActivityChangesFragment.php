<?php

declare(strict_types=1);

namespace App\Admin\Ui\Fragments;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use App\Shared\Audit\Models\Activity;
use App\Shared\Ui\Fragments\ActivityChangesFragment;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('admin.realm-activity-changes', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
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
