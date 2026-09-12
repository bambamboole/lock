<?php
declare(strict_types=1);

namespace App\Audit\Ui\Tables;

use App\Audit\Models\AdminEvent;
use App\Audit\Ui\Fragments\InstanceAdminEventContextFragment;
use App\Shared\Audit\Enums\AdminEventCategory;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('admin.admin-events', can: Scope::AdminEventsRead)]
final class InstanceAdminEventsTable extends AdminEventsTable
{
    /**
     * @return Builder<AdminEvent>
     */
    protected function scopedEvents(): Builder
    {
        return AdminEvent::global();
    }

    protected function langPrefix(): string
    {
        return 'admin.admin-events';
    }

    protected function categories(): array
    {
        return [AdminEventCategory::User, AdminEventCategory::Realm];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function contextFragment(array $row): Fragment
    {
        return Fragment::lazy(InstanceAdminEventContextFragment::class, ['adminEvent' => $row['id']]);
    }
}
