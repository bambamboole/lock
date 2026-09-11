<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\RealmAuditEventsTable;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/events', name: 'admin.realms.events', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmEventsPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.events.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.events'), route('admin.realms.events', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-events-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.events.heading'), __('admin.events.description', ['realm' => $realm->name])),
                    Table::use(RealmAuditEventsTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
