<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\CreateResourceScopeAction;
use App\Admin\Ui\Actions\DeleteResourceAction;
use App\Admin\Ui\Actions\UpdateResourceAction;
use App\Admin\Ui\Tables\AdminResourceScopesTable;
use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use Bambamboole\LaravelOidc\Server\Shared\Tokens\RealmAudiences;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/resources/{resource}', name: 'admin.realms.resources.show', can: [AdminPermission::Access, AdminPermission::ViewResources])]
final class ResourceDetailPage extends AdminPage
{
    private ?Realm $realm = null;

    private ?Resource $resource = null;

    public function title(): ?string
    {
        return $this->resource?->name;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->realm instanceof Realm || ! $this->resource instanceof Resource) {
            return [];
        }

        return [
            Breadcrumb::make($this->realm->name, route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('navigation.resources'), route('admin.realms.resources', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make($this->resource->name, route('admin.realms.resources.show', ['realm' => $this->realm->slug, 'resource' => $this->resource->id], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm, Resource $resource, RealmAudiences $audiences): PageSchema
    {
        abort_unless($resource->belongsToRealm($realm), 404);

        $this->realm = $realm;
        $this->resource = $resource;
        $context = ['realm' => $realm->slug, 'resource' => $resource->id];

        return $schema->schema([
            Stack::make('admin-resource-detail-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make($resource->name, __('admin.resources.detail.description', ['realm' => $realm->name]), ActionBar::make(
                        primary: [Action::use(CreateResourceScopeAction::class, $context)],
                        overflow: [
                            Action::use(UpdateResourceAction::class, $context),
                            Action::use(DeleteResourceAction::class, $context),
                        ],
                        key: 'admin-resource',
                    )),
                    $this->settingsCard($realm, $resource, $audiences),
                    Table::use(AdminResourceScopesTable::class, $context),
                ]),
        ]);
    }

    /**
     * The audience column is what a client sends as the RFC 8707 `resource`, so
     * it is shown resolved rather than as the stored path.
     */
    private function settingsCard(Realm $realm, Resource $resource, RealmAudiences $audiences): Card
    {
        $audience = $realm->runAsCurrent(fn (): string => $resource->isAbsolute()
            ? $resource->identifier
            : $audiences->protectedResource($resource->identifier));

        return Card::make(__('admin.resources.detail.settings.heading'), __('admin.resources.detail.settings.subtitle'))->schema([
            DescriptionList::make('resource-settings')->bleed()->schema([
                TextEntry::make('name', __('admin.resources.fields.name.label'), 'resource-name')->value($resource->name),
                TextEntry::make('identifier', __('admin.resources.columns.identifier'), 'resource-identifier')->value($resource->identifier),
                ComponentEntry::make('audience', __('admin.resources.detail.audience'), 'resource-audience')
                    ->value(Text::make($audience)->copyable()),
            ]),
        ]);
    }
}
