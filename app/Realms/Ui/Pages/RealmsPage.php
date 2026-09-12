<?php
declare(strict_types=1);

namespace App\Realms\Ui\Pages;

use App\Auth\Models\User;
use App\Realms\Ui\Tables\RealmsTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Pages\AdminPage;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Facades\Effects;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Button;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms', name: 'admin.realms', can: Scope::RealmsRead)]
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
            $this->stack(
                key: 'admin-realms-page',
                heading: __('admin.realms.heading'),
                description: __('admin.realms.description'),
                headerActions: ActionBar::make(primary: [
                    Button::make(__('admin.realms.create.submit'))
                        ->visible($user instanceof User && $user->can(Scope::RealmsWrite))
                        ->effects(Effects::redirect(route('admin.realms.create', [], false))),
                ]),
                schema: [
                    Table::use(RealmsTable::class),
                ],
            ),
        ]);
    }
}
