<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Fragments\AdminActivityChangesFragment;
use App\Shared\Audit\Models\Activity;
use App\Shared\Ui\Tables\ActivityTable;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('admin.activity', can: AdminPermission::Access)]
final class AdminActivityTable extends ActivityTable
{
    /**
     * @return Builder<Activity>
     */
    protected function scopedActivity(): Builder
    {
        return Activity::global();
    }

    protected function langPrefix(): string
    {
        return 'admin.activity';
    }

    protected function eventOptions(): array
    {
        $options = [];

        foreach (AdminAuditEvent::cases() as $event) {
            $options[$event->value] = __($event->translationKey());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function changesFragment(array $row): Fragment
    {
        return Fragment::lazy(AdminActivityChangesFragment::class, ['activity' => $row['id']]);
    }
}
