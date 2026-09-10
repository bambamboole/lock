<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsRealmSettingRows;
use App\Admin\Ui\Forms\DeleteRealmForm;
use App\Admin\Ui\Forms\UpdateRealmForm;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Realms\Enums\RealmSettingSection;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
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

#[AsPage(route: '/admin/realms/{realm}/settings', name: 'admin.realms.settings', can: [AdminPermission::Access, AdminPermission::ViewRealms])]
final class RealmSettingsPage extends AdminPage
{
    use BuildsRealmSettingRows;

    private ?Realm $realm = null;

    public function title(): ?string
    {
        return $this->realm?->name;
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
            Breadcrumb::make($this->realm->name, route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('navigation.settings'), route('admin.realms.settings', ['realm' => $this->realm->slug], false)),
        ];
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
                    PageHeader::make(__('admin.realms.settings.heading'), __('admin.realms.settings.description', ['realm' => $realm->name])),
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
                            ...array_map(
                                fn (RealmSettingSection $section): Tab => $this->sectionTab($realm, $section, $manages),
                                RealmSettingSection::cases(),
                            ),
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

    private function sectionTab(Realm $realm, RealmSettingSection $section, bool $manages): Tab
    {
        return Tab::make($section->value, __('admin.realms.sections.'.$section->value))
            ->visible($manages)
            ->schema([
                Card::make(__('admin.realms.sections.'.$section->value))->schema([
                    $this->settingRows($realm, $section, $manages),
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
