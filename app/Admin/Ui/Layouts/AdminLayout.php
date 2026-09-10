<?php
declare(strict_types=1);

namespace App\Admin\Ui\Layouts;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Pages\ActivityPage;
use App\Admin\Ui\Pages\RealmsPage;
use App\Admin\Ui\Pages\UsersPage;
use App\Auth\Models\User;
use App\Shared\Ui\Components\BrandMark;
use App\Shared\Ui\Concerns\RendersUserMenu;
use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsLayout;
use Lattice\Core\Enums\Breakpoint;
use Lattice\Core\Support\Affix;
use Lattice\Facades\Effects;
use Lattice\Layouts\Components\Outlet;
use Lattice\Layouts\LayoutDefinition;
use Lattice\Ui\Components\Breadcrumbs;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Menu;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Components\Sidebar;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Topbar;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Height;
use Lattice\Ui\Enums\Icon;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Side;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsLayout('admin')]
final class AdminLayout extends LayoutDefinition
{
    use RendersUserMenu;

    public function schema(PageSchema $schema, Request $request): PageSchema
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->can(AdminPermission::Access), 403);

        return $schema->schema([
            Stack::make('admin-shell')
                ->direction(Orientation::Horizontal)
                ->height(Height::Screen)
                ->gap(Gap::None)
                ->schema([
                    Sidebar::make('admin-sidebar')->collapsible()->items([
                        BrandMark::wordmark('admin-sidebar-logo'),
                        Menu::make('admin-sidebar-menu')->items([
                            ...($user->can(AdminPermission::ViewRealms) ? [
                                MenuItem::fromPage(RealmsPage::class)->label(__('navigation.realms'))->prefix(Icon::List),
                            ] : []),
                            ...($user->can(AdminPermission::ManageUsers) ? [
                                MenuItem::fromPage(UsersPage::class)->label(__('navigation.users'))->prefix(Affix::icon('users')),
                            ] : []),
                            MenuItem::fromPage(ActivityPage::class)->label(__('navigation.activity'))->prefix(Icon::Clock),
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
                                $this->backToAppButton($user),
                                Breadcrumbs::make('admin-breadcrumbs')->visibleFrom(Breakpoint::Md),
                                Stack::make('admin-topbar-end')
                                    ->float(Side::End)
                                    ->width(Width::Auto)
                                    ->direction(Orientation::Horizontal)
                                    ->align(Align::Center)
                                    ->gap(Gap::Small)
                                    ->schema([
                                        $this->userMenu($user, [
                                            $this->profileMenuItem(),
                                            $this->logoutMenuItem(),
                                        ]),
                                    ]),
                            ]),
                            Outlet::make(),
                        ]),
                ]),
        ]);
    }

    private function backToAppButton(User $user): Button
    {
        return Button::make(__('navigation.back-to-app'))
            ->icon(Icon::ChevronLeft)
            ->emphasis(Emphasis::Ghost)
            ->effects(Effects::redirect(route('profile.edit', absolute: false)));
    }
}
