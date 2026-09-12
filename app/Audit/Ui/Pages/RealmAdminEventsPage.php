<?php
declare(strict_types=1);

namespace App\Audit\Ui\Pages;

use App\Audit\Ui\Tables\RealmAdminEventsTable;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/admin-events', name: 'admin.realms.admin-events', can: Scope::AdminEventsRead)]
final class RealmAdminEventsPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.realm-admin-events.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.admin-events'), route('admin.realms.admin-events', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-admin-events-page')
                ->gap(Gap::Large)
                ->width(Width::ExtraLarge)
                ->schema([
                    PageHeader::make(__('admin.realm-admin-events.heading'), __('admin.realm-admin-events.description', ['realm' => $realm->name])),
                    Table::use(RealmAdminEventsTable::class),
                ]),
        ]);
    }
}
