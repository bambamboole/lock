<?php

declare(strict_types=1);

namespace App\Audit\Ui\Pages;

use App\Audit\Ui\Tables\InstanceActivityTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/activity', name: 'admin.activity', can: Scope::ActivityRead)]
final class ActivityPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.activity.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-activity-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.activity.heading')),
                    Table::use(InstanceActivityTable::class),
                ]),
        ]);
    }
}
