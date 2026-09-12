<?php
declare(strict_types=1);

namespace App\Audit\Ui\Pages;

use App\Audit\Ui\Tables\InstanceAdminEventsTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Table\Components\Table;
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
            $this->stack(
                key: 'admin-events-page',
                heading: __('admin.admin-events.heading'),
                schema: [
                    Table::use(InstanceAdminEventsTable::class),
                ],
            ),
        ]);
    }
}
