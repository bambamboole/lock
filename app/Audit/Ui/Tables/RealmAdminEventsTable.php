<?php
declare(strict_types=1);

namespace App\Audit\Ui\Tables;

use App\Audit\Models\AdminEvent;
use App\Audit\Ui\Fragments\RealmAdminEventContextFragment;
use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AdminEventCategory;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;

#[AsTable('admin.realm-admin-events', can: Scope::AdminEventsRead)]
final class RealmAdminEventsTable extends AdminEventsTable
{
    /**
     * @return Builder<AdminEvent>
     */
    protected function scopedEvents(): Builder
    {
        return AdminEvent::forRealm($this->contextModel('realm', Realm::class));
    }

    protected function langPrefix(): string
    {
        return 'admin.realm-admin-events';
    }

    protected function categories(): array
    {
        return [AdminEventCategory::Realm, AdminEventCategory::Client, AdminEventCategory::Role, AdminEventCategory::Resource];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function contextFragment(array $row): Fragment
    {
        return Fragment::lazy(RealmAdminEventContextFragment::class, ['adminEvent' => $row['id']]);
    }
}
