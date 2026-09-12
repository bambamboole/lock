<?php

declare(strict_types=1);

namespace App\Realms\Ui\Pages;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Ui\Actions\CheckRealmDomainAction;
use App\Realms\Ui\Concerns\BuildsRealmSettingRows;
use App\Realms\Ui\Concerns\OpensRealmDeletion;
use App\Realms\Ui\Concerns\PresentsRealmDomainStatus;
use App\Realms\Ui\Forms\UpdateRealmDomainForm;
use App\Realms\Ui\Forms\UpdateRealmForm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Realms\Enums\RealmSettingSection;
use App\Shared\Ui\Components\ActionBar;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Illuminate\Http\Request;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Tab;
use Lattice\Ui\Components\Tabs;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/settings', name: 'admin.realms.settings', can: ApiScope::RealmsRead)]
final class RealmSettingsPage extends AdminPage
{
    use BuildsRealmSettingRows;
    use OpensRealmDeletion;
    use PresentsRealmDomainStatus;

    public function render(PageSchema $schema, Realm $realm, Request $request): PageSchema
    {
        $user = $request->user();
        $manages = $user instanceof User && $user->can(ApiScope::RealmsWrite);

        $schema->title($realm->name)->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.settings'), route('admin.realms.settings', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-realm-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make(
                        __('admin.realms.settings.heading'),
                        __('admin.realms.settings.description', ['realm' => $realm->name]),
                        ActionBar::make(overflow: [$this->deleteRealmTrigger($realm->slug)], key: 'admin-realm'),
                    ),
                    Tabs::make('realm-tabs')
                        ->orientation(Orientation::Vertical)
                        ->defaultValue('general')
                        ->schema([
                            Tab::make('general', __('admin.realms.tabs.general'))->schema([
                                $this->generalCard($realm, $manages),
                            ]),
                            ...array_map(
                                fn (RealmSettingSection $section): Tab => $this->sectionTab($realm, $section, $manages),
                                RealmSettingSection::cases(),
                            ),
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

    private function generalCard(Realm $realm, bool $manages): Card
    {
        $name = TextEntry::make('name', __('admin.realms.fields.name.label'), 'realm-name')->value($realm->name);

        return Card::make(__('admin.realms.general.heading'), __('admin.realms.general.subtitle'))->schema([
            DescriptionList::make('realm-general')->bleed()->schema([
                $manages
                    ? $name->disclosure([Form::use(UpdateRealmForm::class)])
                    : $name,
                $this->domainEntry($realm, $manages),
                TextEntry::make('slug', __('admin.realms.fields.slug.label'), 'realm-slug')
                    ->value($realm->slug)
                    ->description(__('admin.realms.fields.slug.help-text')),
                TextEntry::make('users', __('admin.realms.summary.users'), 'realm-users')
                    ->value((string) $realm->users()->count()),
                DateEntry::make('created_at', __('admin.realms.summary.created-at'), 'realm-created-at')
                    ->value($realm->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    private function domainEntry(Realm $realm, bool $manages): ComponentEntry
    {
        $entry = ComponentEntry::make('domain', __('admin.realms.fields.domain.label'), 'realm-domain');

        if ($realm->isMaster()) {
            return $entry->value(Text::make($realm->host()))->description(__('admin.realms.domain.master'));
        }

        $status = $realm->domain_status;
        $outcome = __('admin.realms.domain.outcome.'.$status->value, ['domain' => $realm->host()]);
        $checkedAt = $realm->domain_checked_at === null ? '' : ' '.__('admin.realms.domain.checked-at', ['time' => $realm->domain_checked_at->diffForHumans()]);

        $entry
            ->value(
                Stack::make('realm-domain-value')
                    ->direction(Orientation::Horizontal)
                    ->width(Width::Auto)
                    ->align(Align::Center)
                    ->gap(Gap::Small)
                    ->schema([
                        Text::make($realm->host()),
                        Badge::make(__('admin.realms.domain.status.'.$status->value))->color($this->domainStatusColor($status)),
                        ...($manages ? [Action::use(CheckRealmDomainAction::class)] : []),
                    ]),
            )
            ->description(trim($outcome.$checkedAt.($realm->domain_check_error === null ? '' : ' ('.$realm->domain_check_error.')')));

        return $manages ? $entry->disclosure([Form::use(UpdateRealmDomainForm::class)]) : $entry;
    }
}
