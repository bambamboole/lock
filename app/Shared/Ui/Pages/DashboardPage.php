<?php
declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Shared\Ui\Components\PageHeader;
use App\Tenants\Models\Tenant;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '{current_tenant}/dashboard', name: 'dashboard')]
final class DashboardPage extends TenantPage
{
    use ResolvesCurrentUser;

    private ?Tenant $tenant = null;

    public function title(): string
    {
        return __('dashboard.title');
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->tenant instanceof Tenant) {
            return [];
        }

        return [
            Breadcrumb::make(
                __('dashboard.title'),
                route('dashboard', ['current_tenant' => $this->tenant->slug], absolute: false),
            ),
        ];
    }

    public function render(PageSchema $schema, Request $request, Tenant $current_tenant): PageSchema
    {
        $user = $this->currentUser();

        $this->tenant = $current_tenant;

        return $schema->schema([
            Stack::make('dashboard-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make(
                        __('dashboard.heading'),
                        __('dashboard.welcome', ['name' => $user->name, 'tenant' => $current_tenant->name]),
                    ),
                ]),
        ]);
    }
}
