<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\AdminRealmsTable;
use App\Auth\Models\User;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Facades\Effects;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms', name: 'admin.realms', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmsPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.realms.heading');
    }

    public function render(PageSchema $schema, Request $request): PageSchema
    {
        $user = $request->user();

        return $schema->schema([
            Stack::make('admin-realms-page')->gap(Gap::Large)->schema([
                PageHeader::make(__('admin.realms.heading'), __('admin.realms.description'), ActionBar::make(primary: [
                    Button::make(__('admin.realms.create.submit'))
                        ->visible($user instanceof User && $user->can(AdminPermission::ManageRealms))
                        ->effects(Effects::redirect(route('admin.realms.create', [], false))),
                ])),
                Table::use(AdminRealmsTable::class),
            ]),
        ]);
    }
}
