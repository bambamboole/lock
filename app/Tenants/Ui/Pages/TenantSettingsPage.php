<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Pages;

use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\TenantPage;
use App\Tenants\Models\Tenant;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Tabs;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;
use Lattice\Ui\Slot;

#[AsPage(route: 'tenants/{current_tenant}/settings', name: 'tenant.settings.edit')]
final class TenantSettingsPage extends TenantPage
{
    private ?Tenant $tenant = null;

    public function title(): ?string
    {
        return $this->tenant?->name;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->tenant instanceof Tenant) {
            return [];
        }

        return [Breadcrumb::make(
            $this->tenant->name,
            route('tenant.settings.edit', ['current_tenant' => $this->tenant->slug], absolute: false),
        )];
    }

    public function render(PageSchema $schema, Tenant $current_tenant): PageSchema
    {
        $this->tenant = $current_tenant;

        return $schema->schema([
            Stack::make('tenant-settings-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make($current_tenant->name, __('tenant.settings.subtitle')),
                    Tabs::make('tenant-settings-tabs')
                        ->orientation(Orientation::Vertical)
                        ->defaultValue('general')
                        ->schema([
                            Slot::make('tenant.settings.tabs')->context(['tenant' => $current_tenant]),
                        ]),
                ]),
        ]);
    }
}
