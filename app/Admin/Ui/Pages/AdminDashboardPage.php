<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin', name: 'admin.dashboard')]
final class AdminDashboardPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.dashboard.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-dashboard-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make(__('admin.dashboard.heading')),
                    Grid::make('admin-dashboard-overview')
                        ->columns(3)
                        ->schema([
                            Card::make(__('admin.dashboard.cards.users.title'), (string) User::query()->count()),
                            Card::make(__('admin.dashboard.cards.realms.title'), (string) Realm::query()->count()),
                            Card::make(__('admin.dashboard.cards.admins.title'), (string) User::query()->whereHas('globalRoles')->count()),
                        ]),
                ]),
        ]);
    }
}
