<?php
declare(strict_types=1);

namespace App\Realms\Ui\Fragments;

use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Activity;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Shared\Ui\Fragments\ActivityChangesFragment as BaseActivityChangesFragment;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('realm.settings.activity-changes', can: RealmPermission::ViewAudit, on: 'realm')]
final class ActivityChangesFragment extends BaseActivityChangesFragment
{
    use ActivatesRealmContext;

    protected function activity(): Activity
    {
        /** @var Activity $activity */
        $activity = $this->contextModel('activity');

        return $activity;
    }

    protected function emptyText(): string
    {
        return __('realm.settings.activity.detail.empty');
    }
}
