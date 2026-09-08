<?php

declare(strict_types=1);

namespace App\Admin\Ui\Fragments;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Activity;
use App\Shared\Ui\Fragments\ActivityChangesFragment;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('admin.activity-changes', can: AdminPermission::Access)]
final class AdminActivityChangesFragment extends ActivityChangesFragment
{
    protected function activity(): ?Activity
    {
        return Activity::global()->find($this->contextInt('activity'));
    }

    protected function emptyText(): string
    {
        return __('admin.activity.detail.empty');
    }
}
