<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Tables;

use App\Shared\Ui\Tables\ActivityTable;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use App\Tenants\Ui\Fragments\ActivityChangesFragment;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('tenant.settings.activity', can: TenantPermission::ViewAudit, on: 'tenant')]
final class TenantActivityTable extends ActivityTable
{
    use ActivatesTenantContext;

    /**
     * @return Builder<Activity>
     */
    protected function scopedActivity(): Builder
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return Activity::query()->forTenant($tenant);
    }

    protected function langPrefix(): string
    {
        return 'tenant.settings.activity';
    }

    protected function eventOptions(): array
    {
        $options = [];

        foreach (['created', 'updated', 'deleted'] as $lifecycle) {
            $options[$lifecycle] = __("audit.events.model.{$lifecycle}");
        }

        foreach (TenantAuditEvent::cases() as $event) {
            $options[$event->value] = __($event->translationKey());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function changesFragment(array $row): Fragment
    {
        return Fragment::lazy(ActivityChangesFragment::class, [
            'activity' => $row['id'],
        ]);
    }
}
