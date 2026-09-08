<?php
declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use App\Realms\Models\Realm;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: 'dashboard', name: 'dashboard')]
final class DashboardPage extends RealmPage
{
    use ResolvesCurrentUser;

    private ?Realm $realm = null;

    public function title(): string
    {
        return __('dashboard.title');
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
            Breadcrumb::make(
                __('dashboard.title'),
                route('dashboard', absolute: false),
            ),
        ];
    }

    public function render(PageSchema $schema, Request $request): PageSchema
    {
        $user = $this->currentUser();

        $this->realm = $user->realm;

        return $schema->schema([
            Stack::make('dashboard-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make(
                        __('dashboard.heading'),
                        __('dashboard.welcome', ['name' => $user->name, 'realm' => $user->realm->name]),
                    ),
                ]),
        ]);
    }
}
