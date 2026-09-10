<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Facades\Effects;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/users', name: 'admin.realms.users', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class RealmUsersPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.users.heading');
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
            Breadcrumb::make(__('navigation.users'), route('admin.realms.users', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-users-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.users.heading'), __('admin.users.description', ['realm' => $realm->name]), [
                        Button::make(__('admin.users.create.submit'))
                            ->effects(Effects::redirect(route('admin.realms.users.create', ['realm' => $realm->slug], false))),
                    ]),
                    Table::use(AdminUsersTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
