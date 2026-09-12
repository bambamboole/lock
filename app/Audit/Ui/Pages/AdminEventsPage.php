<?php
declare(strict_types=1);

namespace App\Audit\Ui\Pages;

use App\Audit\Ui\Tables\InstanceAdminEventsTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/events', name: 'admin.admin-events', can: Scope::AdminEventsRead)]
final class AdminEventsPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.admin-events.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-events-page')
                ->gap(Gap::Large)
                ->width(Width::ExtraLarge)
                ->schema([
                    PageHeader::make(__('admin.admin-events.heading')),
                    Table::use(InstanceAdminEventsTable::class),
                ]),
        ]);
    }
}
