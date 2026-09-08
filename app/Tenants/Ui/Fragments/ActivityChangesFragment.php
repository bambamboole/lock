<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Fragments;

use App\Shared\Ui\Fragments\ActivityChangesFragment as BaseActivityChangesFragment;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Activity;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Lattice\Core\Attributes\AsFragment;

#[AsFragment('tenant.settings.activity-changes', can: TenantPermission::ViewAudit, on: 'tenant')]
final class ActivityChangesFragment extends BaseActivityChangesFragment
{
    use ActivatesTenantContext;

    protected function activity(): Activity
    {
        /** @var Activity $activity */
        $activity = $this->contextModel('activity');

        return $activity;
    }

    protected function emptyText(): string
    {
        return __('tenant.settings.activity.detail.empty');
    }
}
