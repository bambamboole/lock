<?php
declare(strict_types=1);

namespace App\Shared\Ui\Layouts;

use App\Auth\Models\User;
use App\Shared\Ui\Components\BrandMark;
use App\Shared\Ui\Concerns\RendersUserMenu;
use App\Shared\Ui\Pages\DashboardPage;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsLayout;
use Lattice\Core\Enums\Breakpoint;
use Lattice\Core\Enums\ColorName;
use Lattice\Facades\Effects;
use Lattice\Layouts\Components\Outlet;
use Lattice\Layouts\LayoutDefinition;
use Lattice\Notifications\Components\Notifications;
use Lattice\Ui\Components\Breadcrumbs;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Callouts;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Menu;
use Lattice\Ui\Components\MenuItem;
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
                            $this->realmLabel($user, 'realm-label-mobile')
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
                                $this->realmLabel($user, 'realm-label')->visibleFrom(Breakpoint::Md),
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
        $items = [];

        $items[] = Button::make(__('navigation.realm-settings'))
            ->icon(Icon::Settings)
            ->emphasis(Emphasis::Ghost)
            ->effects(Effects::redirect(route('realm.settings.edit', absolute: false)));

        $items[] = Notifications::make('app-notifications');
        $items[] = $this->userMenu($user, [
            $this->profileMenuItem(),
            Slot::make('app.user-menu'),
            $this->logoutMenuItem(),
        ]);

        return $items;
    }

    /**
     * A user belongs to exactly one realm, so this names where you are rather
     * than offering somewhere to go.
     */
    private function realmLabel(User $user, string $key): Text
    {
        return Text::make($user->realm->name, $key)->size(Size::Sm);
    }
}
