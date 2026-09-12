<?php

declare(strict_types=1);

namespace App\Audit\Ui\Pages;

use App\Audit\Ui\Tables\RealmActivityTable;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/activity', name: 'admin.realms.activity', can: Scope::ActivityRead)]
final class RealmActivityPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.realm-activity.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.activity'), route('admin.realms.activity', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-activity-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.realm-activity.heading'), __('admin.realm-activity.description', ['realm' => $realm->name])),
                    Table::use(RealmActivityTable::class),
                ]),
        ]);
    }
}
