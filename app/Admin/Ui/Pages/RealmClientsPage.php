<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\AdminClientsTable;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Facades\Effects;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/clients', name: 'admin.realms.clients', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class RealmClientsPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.clients.heading');
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
            Breadcrumb::make(__('navigation.clients'), route('admin.realms.clients', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-clients-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.clients.heading'), __('admin.clients.description', ['realm' => $realm->name]), ActionBar::make(primary: [
                        Button::make(__('admin.clients.create.submit'))
                            ->effects(Effects::redirect(route('admin.realms.clients.create', ['realm' => $realm->slug], false))),
                    ])),
                    Table::use(AdminClientsTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
