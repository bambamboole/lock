<?php
declare(strict_types=1);

namespace App\Shared\Ui\Layouts;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Shared\Ui\Components\BrandMark;
use App\Shared\Ui\Concerns\RendersUserMenu;
use App\Shared\Ui\Pages\DashboardPage;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Actions\SwitchTenant;
use App\Tenants\Ui\Forms\CreateTenantForm;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsLayout;
use Lattice\Core\Enums\Breakpoint;
use Lattice\Core\Enums\ColorName;
use Lattice\Facades\Effects;
use Lattice\Form\Components\Form;
use Lattice\Layouts\Components\Outlet;
use Lattice\Layouts\LayoutDefinition;
use Lattice\Notifications\Components\Notifications;
use Lattice\Ui\Components\Breadcrumbs;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Callouts;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Dropdown;
use Lattice\Ui\Components\Icon as IconComponent;
use Lattice\Ui\Components\Menu;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Components\Modal;
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

#[AsLayout('app')]
final class AppLayout extends LayoutDefinition
{
    use RendersUserMenu;

    public function schema(PageSchema $schema, Request $request): PageSchema
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $schema->schema([
            Stack::make('app-shell')
                ->direction(Orientation::Horizontal)
                ->height(Height::Screen)
                ->gap(Gap::None)
                ->schema([
                    Sidebar::make('app-sidebar')
                        ->collapsible()
                        ->items([
                            BrandMark::wordmark('app-sidebar-logo'),
                            Menu::make('sidebar')->items([
                                MenuItem::fromPage(DashboardPage::class)->label(__('navigation.dashboard'))->prefix(Icon::LayoutDashboard),
                                Slot::make('app.navigation'),
                            ]),
                        ])
                        ->footer([
                            $this->tenantSwitcher($user, Placement::Top)
                                ->key('tenant-switcher-mobile')
                                ->hiddenFrom(Breakpoint::Md),
                        ]),
                    Stack::make('app-main')
                        ->width(Width::Fill)
                        ->schema([
                            Topbar::make('app-topbar')->sticky()->items([
                                // The sidebar ships no trigger of its own: without this
                                // button the mobile drawer can never open and the
                                // desktop rail can never collapse.
                                Button::make(__('navigation.toggle-sidebar'), 'sidebar-toggle')
                                    ->icon(Icon::PanelLeft)
                                    ->emphasis(Emphasis::Ghost)
                                    ->effects(Effects::toggleSidebar('app-sidebar')),
                                BrandMark::make('app-topbar-logo')->hiddenFrom(Breakpoint::Md),
                                $this->tenantSwitcher($user)->visibleFrom(Breakpoint::Md),
                                Text::make('/')->size(Size::Sm)->color(ColorName::Muted)->visibleFrom(Breakpoint::Md),
                                Breadcrumbs::make('app-breadcrumbs')->visibleFrom(Breakpoint::Md),
                                Stack::make('topbar-end')
                                    ->float(Side::End)
                                    ->width(Width::Auto)
                                    ->direction(Orientation::Horizontal)
                                    ->align(Align::Center)
                                    ->gap(Gap::Small)
                                    ->schema($this->topbarTrailing($user)),
                            ]),
                            Callouts::make('app-callouts'),
                            Outlet::make(),
                        ]),
                ]),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    private function topbarTrailing(User $user): array
    {
        $currentTenant = $user->currentTenant;

        $items = [];

        if ($currentTenant instanceof Tenant) {
            $items[] = Button::make(__('navigation.tenant-settings'))
                ->icon(Icon::Settings)
                ->emphasis(Emphasis::Ghost)
                ->effects(Effects::redirect(route('tenant.settings.edit', ['current_tenant' => $currentTenant->slug], false)));
        }

        $items[] = Notifications::make('app-notifications');
        $items[] = $this->userMenu($user, $this->userMenuItems($user));

        return $items;
    }

    private function tenantSwitcher(User $user, Placement $placement = Placement::Bottom): Dropdown
    {
        $currentTenant = $user->currentTenant;

        $items = $user->tenants
            ->map(function (Tenant $tenant) use ($currentTenant): MenuItem {
                $item = MenuItem::make($tenant->name)
                    ->action(SwitchTenant::class, ['tenant' => $tenant->slug]);

                return $tenant->is($currentTenant) ? $item->suffix(Icon::Check) : $item;
            })
            ->all();

        $items[] = MenuItem::make(__('tenant.create.menu.label'))
            ->modal(
                Modal::make('tenants.create-modal')
                    ->title(__('tenant.create.title'))
                    ->schema([
                        Form::use(CreateTenantForm::class),
                    ]),
            )
            ->prefix(Icon::Plus);

        return Dropdown::make('tenant-switcher')
            ->placement($placement)
            ->trigger($this->dropdownTrigger('users', $currentTenant->name ?? __('navigation.select-tenant')))
            ->items($items);
    }

    /**
     * @return array<int, MenuItem>
     */
    private function userMenuItems(User $user): array
    {
        $items = [$this->profileMenuItem()];

        if ($user->can(AdminPermission::Access)) {
            $items[] = MenuItem::make(__('navigation.admin'))
                ->href(route('admin.dashboard', absolute: false))
                ->prefix(Icon::Settings);
        }

        $items[] = $this->logoutMenuItem();

        return $items;
    }

    /**
     * @return array<int, Component>
     */
    private function dropdownTrigger(string $icon, string $label): array
    {
        return [
            Stack::make()
                ->direction(Orientation::Horizontal)
                ->align(Align::Center)
                ->gap(Gap::Small)
                ->schema([
                    IconComponent::make($icon),
                    Text::make($label)
                        ->size(Size::Sm)
                        ->color(ColorName::Default),
                ]),
        ];
    }
}
