<?php
declare(strict_types=1);

namespace App\Roles\Ui\Pages;

use App\Realms\Models\Realm;
use App\Roles\Ui\Actions\CreateRoleAction;
use App\Roles\Ui\Tables\RolesTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/roles', name: 'admin.realms.roles', can: Scope::RolesRead)]
final class RolesPage extends AdminPage
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
            $this->stack(
                key: 'admin-realm-roles-page',
                heading: __('admin.roles.heading'),
                description: __('admin.roles.description', ['realm' => $realm->name]),
                headerActions: ActionBar::make(primary: [
                    Action::use(CreateRoleAction::class),
                ]),
                schema: [
                    Table::use(RolesTable::class),
                ],
            ),
        ]);
    }
}
