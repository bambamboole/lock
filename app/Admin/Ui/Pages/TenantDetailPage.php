<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Shared\Ui\Components\PageHeader;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\TenantMemberRoles;
use Illuminate\Support\Collection;
use Lattice\Core\Attributes\AsPage;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\BooleanEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/tenants/{tenant}', name: 'admin.tenants.show', can: [AdminPermission::Access, AdminPermission::ManageTenants])]
final class TenantDetailPage extends AdminPage
{
    private ?Tenant $tenant = null;

    public function title(): ?string
    {
        return $this->tenant?->name;
    }

    public function render(PageSchema $schema, Tenant $tenant, TenantMemberRoles $memberRoles): PageSchema
    {
        $this->tenant = $tenant;

        return $schema->schema([
            Stack::make('admin-tenant-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Medium)
                ->schema([
                    PageHeader::make($tenant->name),
                    $this->summaryCard($tenant),
                    $this->membersCard($memberRoles->rows($tenant)),
                ]),
        ]);
    }

    private function summaryCard(Tenant $tenant): Card
    {
        return Card::make(__('admin.tenants.detail.summary.heading'))->schema([
            DescriptionList::make('tenant-summary')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'))->value($tenant->name),
                TextEntry::make('slug', __('admin.tenants.detail.slug'))->value($tenant->slug),
                BooleanEntry::make('is_personal', __('admin.tenants.detail.personal'))->value($tenant->is_personal),
                TextEntry::make('owner', __('admin.tenants.detail.owner'))
                    ->value($tenant->owner()?->name)
                    ->placeholder(__('admin.tenants.detail.no-owner')),
                DateEntry::make('created_at', __('admin.tenants.detail.created-at'))
                    ->value($tenant->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    /**
     * @param  Collection<int, array{id: string, name: string, email: string, role_labels: string, is_owner: bool}>  $members
     */
    private function membersCard(Collection $members): Card
    {
        return Card::make(__('admin.tenants.detail.members.heading'))->schema([
            DescriptionList::make('tenant-members')
                ->bleed()
                ->emptyLabel(__('admin.tenants.detail.no-members'))
                ->schema(
                    $members
                        ->map(fn (array $member) => TextEntry::make('member-'.$member['id'], $member['name'])
                            ->value($member['role_labels'] !== '' ? $member['role_labels'] : null)
                            ->placeholder(__('admin.tenants.detail.no-roles')))
                        ->all(),
                ),
        ]);
    }
}
