<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Ui\Actions\CreateRealmRoleAction;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Realms\Models\Realm;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/roles', name: 'admin.realms.roles', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class RealmRolesPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.roles.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.roles'), route('admin.realms.roles', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-roles-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.roles.heading'), __('admin.roles.description', ['realm' => $realm->name]), ActionBar::make(primary: [
                        Action::use(CreateRealmRoleAction::class),
                    ])),
                    Table::use(AdminRealmRolesTable::class),
                ]),
        ]);
    }
}
