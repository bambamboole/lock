<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/users', name: 'admin.users', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UsersPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.users.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-users-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.users.heading')),
                    Table::use(AdminUsersTable::class),
                ]),
        ]);
    }
}
