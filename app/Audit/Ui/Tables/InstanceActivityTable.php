<?php

declare(strict_types=1);

namespace App\Audit\Ui\Tables;

use App\Audit\Ui\Fragments\InstanceActivityChangesFragment;
use App\Shared\Audit\Models\Activity;
use App\Shared\Auth\Enums\UserAuditEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('admin.activity', can: Scope::ActivityRead)]
final class InstanceActivityTable extends ActivityTable
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

        foreach (UserAuditEvent::cases() as $event) {
            $options[$event->value] = __($event->translationKey());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function changesFragment(array $row): Fragment
    {
        return Fragment::lazy(InstanceActivityChangesFragment::class, ['activity' => $row['id']]);
    }
}
