<?php
declare(strict_types=1);

namespace App\Shared\Ui\Layouts;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Ui\Components\BrandMark;
use App\Shared\Ui\Concerns\RendersUserMenu;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsLayout;
use Lattice\Core\Enums\Breakpoint;
use Lattice\Facades\Effects;
use Lattice\Layouts\Components\Outlet;
use Lattice\Layouts\LayoutDefinition;
use Lattice\Ui\Components\Breadcrumbs;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Dropdown;
use Lattice\Ui\Components\Icon as IconComponent;
use Lattice\Ui\Components\Menu;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Components\Separator;
use Lattice\Ui\Components\Sidebar;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Components\Topbar;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Height;
use Lattice\Ui\Enums\Icon;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Placement;
use Lattice\Ui\Enums\Side;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;
use Lattice\Ui\Slot;

#[AsLayout('admin')]
final class AdminLayout extends LayoutDefinition
{
    use RendersUserMenu;

    public function schema(PageSchema $schema, Request $request): PageSchema
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->managementScopes()->isNotEmpty(), 403);

        $realm = $this->currentRealm($request);

        return $schema->schema([
            Stack::make('admin-shell')
                ->direction(Orientation::Horizontal)
                ->height(Height::Screen)
                ->gap(Gap::None)
                ->schema([
                    Sidebar::make('admin-sidebar')->collapsible()->items([
                        BrandMark::wordmark('admin-sidebar-logo'),
                        ...($user->can(ApiScope::RealmsRead) ? [$this->realmSwitcher($realm)] : []),
                        Menu::make('admin-sidebar-menu')->items([
                            $realm instanceof Realm
                                ? Slot::make('admin.sidebar.realm')->context(['realm' => $realm])
                                : Slot::make('admin.sidebar.instance'),
                        ]),
                    ]),
                    Stack::make('admin-main')
                        ->width(Width::Fill)
                        ->schema([
                            Topbar::make('admin-topbar')->sticky()->items([
                                Button::make(__('navigation.toggle-sidebar'), 'admin-sidebar-toggle')
                                    ->icon(Icon::PanelLeft)
                                    ->emphasis(Emphasis::Ghost)
                                    ->effects(Effects::toggleSidebar('admin-sidebar')),
                                BrandMark::make('admin-topbar-logo')->hiddenFrom(Breakpoint::Md),
                                $this->backToAppButton(),
                                Breadcrumbs::make('admin-breadcrumbs')->visibleFrom(Breakpoint::Md),
                                Stack::make('admin-topbar-end')
                                    ->float(Side::End)
                                    ->width(Width::Auto)
                                    ->direction(Orientation::Horizontal)
                                    ->align(Align::Center)
                                    ->gap(Gap::Small)
                                    ->schema([
                                        $this->userMenu($user, [
                                            $this->accountMenuItem(),
                                            $this->logoutMenuItem(),
                                        ]),
                                    ]),
                            ]),
                            Outlet::make(),
                        ]),
                ]),
        ]);
    }

    private function currentRealm(Request $request): ?Realm
    {
        $realm = $request->route('realm');

        if ($realm instanceof Realm) {
            return $realm;
        }

        return is_string($realm) ? Realm::query()->where('slug', $realm)->first() : null;
    }

    private function realmSwitcher(?Realm $realm): Dropdown
    {
        $realms = Realm::query()->orderBy('name')->get();

        return Dropdown::make('realm-switcher')
            ->placement(Placement::Bottom)
            ->trigger([
                Stack::make()
                    ->direction(Orientation::Horizontal)
                    ->align(Align::Center)
                    ->gap(Gap::Small)
                    ->schema([
                        Text::make($realm instanceof Realm ? $realm->name : __('navigation.select-realm'), 'realm-switcher-label')
                            ->size(Size::Sm)
                            ->hideWhenCollapsed(),
                        IconComponent::make(Icon::ChevronsUpDown)->size(Size::Sm),
                    ]),
            ])
            ->items([
                ...$realms->map(fn (Realm $candidate): MenuItem => $this->realmSwitcherItem($candidate, $candidate->is($realm)))->all(),
                Separator::make(),
                $this->menuItem(__('navigation.all-realms'), 'admin.realms')->prefix(Icon::List),
            ]);
    }

    private function realmSwitcherItem(Realm $realm, bool $current): MenuItem
    {
        $item = $this->menuItem($realm->name, 'admin.realms.users', ['realm' => $realm->slug]);

        return $current ? $item->prefix(Icon::Check) : $item;
    }

    /**
     * Only the realm switcher is built here, and it links by route name:
     * importing a domain's page class would make App\Shared depend on that
     * domain. Every other entry is a domain's own, hung into the sidebar
     * slots, where `MenuItem::fromPage()` is available because the module
     * registering the item owns the page.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function menuItem(string $label, string $route, array $parameters = []): MenuItem
    {
        return MenuItem::make($label)->href(route($route, $parameters, false));
    }

    private function backToAppButton(): Button
    {
        return Button::make(__('navigation.back-to-app'))
            ->icon(Icon::ChevronLeft)
            ->emphasis(Emphasis::Ghost)
            ->effects(Effects::redirect(route('account', absolute: false)));
    }
}
