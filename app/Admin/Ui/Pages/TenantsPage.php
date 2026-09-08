<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\AdminTenantsTable;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/tenants', name: 'admin.tenants', can: [AdminPermission::Access, AdminPermission::ManageTenants])]
final class TenantsPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.tenants.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-tenants-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.tenants.heading')),
                    Table::use(AdminTenantsTable::class),
                ]),
        ]);
    }
}
