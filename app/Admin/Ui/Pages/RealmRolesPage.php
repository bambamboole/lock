<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\CreateRealmRoleAction;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
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
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.roles.heading');
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->realm instanceof Realm) {
            return [];
        }

        return [
            Breadcrumb::make($this->realm->name, route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('navigation.roles'), route('admin.realms.roles', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-roles-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.roles.heading'), __('admin.roles.description', ['realm' => $realm->name]), ActionBar::make(primary: [
                        Action::use(CreateRealmRoleAction::class, ['realm' => $realm->slug]),
                    ])),
                    Table::use(AdminRealmRolesTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
