<?php
declare(strict_types=1);

namespace App\Resources\Ui\Pages;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use App\Resources\Ui\Actions\CreateResourceScopeAction;
use App\Resources\Ui\Actions\DeleteResourceAction;
use App\Resources\Ui\Actions\UpdateResourceAction;
use App\Resources\Ui\Tables\ResourceScopesTable;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
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

#[AsPage(route: '/admin/realms/{realm}/resources/{resource}', name: 'admin.realms.resources.show', can: ApiScope::ResourcesRead)]
final class ResourceDetailPage extends AdminPage
{
    public function render(PageSchema $schema, Realm $realm, Resource $resource, RealmAudiences $audiences): PageSchema
    {
        abort_unless($resource->belongsToRealm($realm), 404);

        $schema->title($resource->name)->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.resources'), route('admin.realms.resources', ['realm' => $realm->slug], false)),
            Breadcrumb::make($resource->name, route('admin.realms.resources.show', ['realm' => $realm->slug, 'resource' => $resource->id], false)),
        ));

        return $schema->schema([
            Stack::make('admin-resource-detail-page')
                ->gap(Gap::Large)
                ->schema([
                    PageHeader::make($resource->name, __('admin.resources.detail.description', ['realm' => $realm->name]), ActionBar::make(
                        primary: [Action::use(CreateResourceScopeAction::class)],
                        overflow: [
                            Action::use(UpdateResourceAction::class),
                            Action::use(DeleteResourceAction::class),
                        ],
                        key: 'admin-resource',
                    )),
                    $this->settingsCard($realm, $resource, $audiences),
                    Table::use(ResourceScopesTable::class),
                ]),
        ]);
    }

    /**
     * The audience is shown resolved, since that is what a client sends as the
     * RFC 8707 `resource`.
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
