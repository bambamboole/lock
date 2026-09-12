<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Ui\Actions\CreateResourceAction;
use App\Admin\Ui\Tables\AdminResourcesTable;
use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
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

#[AsPage(route: '/admin/realms/{realm}/resources', name: 'admin.realms.resources', can: ApiScope::ResourcesRead)]
final class RealmResourcesPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.resources.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.resources'), route('admin.realms.resources', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-resources-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.resources.heading'), __('admin.resources.description', ['realm' => $realm->name]), ActionBar::make(primary: [
                        Action::use(CreateResourceAction::class),
                    ])),
                    Table::use(AdminResourcesTable::class),
                ]),
        ]);
    }
}
