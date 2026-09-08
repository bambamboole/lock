<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\RealmMemberDirectory;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Support\Collection;
use Lattice\Core\Attributes\AsPage;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}', name: 'admin.realms.show', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class RealmDetailPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): ?string
    {
        return $this->realm?->name;
    }

    public function render(PageSchema $schema, Realm $realm, RealmMemberDirectory $memberRoles): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-realm-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Medium)
                ->schema([
                    PageHeader::make($realm->name),
                    $this->summaryCard($realm),
                    $this->membersCard($memberRoles->rows($realm)),
                ]),
        ]);
    }

    private function summaryCard(Realm $realm): Card
    {
        return Card::make(__('admin.realms.detail.summary.heading'))->schema([
            DescriptionList::make('realm-summary')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'))->value($realm->name),
                TextEntry::make('slug', __('admin.realms.detail.slug'))->value($realm->slug),
                TextEntry::make('owner', __('admin.realms.detail.owner'))
                    ->value($realm->owner()?->name)
                    ->placeholder(__('admin.realms.detail.no-owner')),
                DateEntry::make('created_at', __('admin.realms.detail.created-at'))
                    ->value($realm->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    /**
     * @param  Collection<int, array{id: string, name: string, email: string, role_labels: string, is_owner: bool}>  $members
     */
    private function membersCard(Collection $members): Card
    {
        return Card::make(__('admin.realms.detail.members.heading'))->schema([
            DescriptionList::make('realm-members')
                ->bleed()
                ->emptyLabel(__('admin.realms.detail.no-members'))
                ->schema(
                    $members
                        ->map(fn (array $member) => TextEntry::make('member-'.$member['id'], $member['name'])
                            ->value($member['role_labels'] !== '' ? $member['role_labels'] : null)
                            ->placeholder(__('admin.realms.detail.no-roles')))
                        ->all(),
                ),
        ]);
    }
}
