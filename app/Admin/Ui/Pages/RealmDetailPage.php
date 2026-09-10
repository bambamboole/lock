<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\DeleteRealmForm;
use App\Admin\Ui\Forms\UpdateRealmConfigurationForm;
use App\Admin\Ui\Forms\UpdateRealmForm;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Tab;
use Lattice\Ui\Components\Tabs;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}', name: 'admin.realms.show', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmDetailPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): ?string
    {
        return $this->realm?->name;
    }

    public function render(PageSchema $schema, Realm $realm, Request $request): PageSchema
    {
        $this->realm = $realm;
        $user = $request->user();
        $manages = $user instanceof User && $user->can(AdminPermission::ManageRealms);

        return $schema->schema([
            Stack::make('admin-realm-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make($realm->name, $realm->slug),
                    Tabs::make('realm-tabs')
                        ->orientation(Orientation::Vertical)
                        ->defaultValue('general')
                        ->schema([
                            Tab::make('general', __('admin.realms.tabs.general'))->schema([
                                $this->summaryCard($realm),
                                Card::make(__('admin.realms.general.heading'), __('admin.realms.general.subtitle'))
                                    ->visible($manages)
                                    ->schema([Form::use(UpdateRealmForm::class, ['realm' => $realm->slug])]),
                            ]),
                            Tab::make('configuration', __('admin.realms.tabs.configuration'))
                                ->visible($manages)
                                ->schema([Form::use(UpdateRealmConfigurationForm::class, ['realm' => $realm->slug])]),
                            Tab::make('danger', __('admin.realms.tabs.danger'))
                                ->visible($manages && ! $realm->isMaster())
                                ->schema([
                                    Card::make(__('admin.realms.danger.heading'), __('admin.realms.danger.subtitle'))
                                        ->schema([Form::use(DeleteRealmForm::class, ['realm' => $realm->slug])]),
                                ]),
                        ]),
                ]),
        ]);
    }

    private function summaryCard(Realm $realm): Card
    {
        return Card::make(__('admin.realms.summary.heading'))->schema([
            DescriptionList::make('realm-summary')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'))->value($realm->name),
                TextEntry::make('slug', __('admin.realms.summary.slug'))->value($realm->slug),
                TextEntry::make('users', __('admin.realms.summary.users'))->value((string) $realm->users()->count()),
                DateEntry::make('created_at', __('admin.realms.summary.created-at'))
                    ->value($realm->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }
}
