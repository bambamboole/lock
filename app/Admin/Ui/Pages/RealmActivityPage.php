<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\RealmActivityTable;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/activity', name: 'admin.realms.activity', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmActivityPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.realm-activity.heading');
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
            Breadcrumb::make(__('navigation.activity'), route('admin.realms.activity', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-activity-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.realm-activity.heading'), __('admin.realm-activity.description', ['realm' => $realm->name])),
                    Table::use(RealmActivityTable::class, ['realm' => $realm->slug]),
                ]),
        ]);
    }
}
