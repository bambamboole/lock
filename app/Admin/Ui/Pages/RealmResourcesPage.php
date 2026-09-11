<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\CreateResourceAction;
use App\Admin\Ui\Tables\AdminResourcesTable;
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

#[AsPage(route: '/admin/realms/{realm}/resources', name: 'admin.realms.resources', can: [AdminPermission::Access, AdminPermission::ViewResources])]
final class RealmResourcesPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.resources.heading');
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
            Breadcrumb::make(__('navigation.resources'), route('admin.realms.resources', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-resources-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.resources.heading'), __('admin.resources.description', ['realm' => $realm->name]), ActionBar::make(primary: [
                        Action::use(CreateResourceAction::class, ['realm' => $realm->slug]),
                    ])),
                    Table::use(AdminResourcesTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
