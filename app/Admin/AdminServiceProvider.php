<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Enums\Icon;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Lattice::extend('app.user-menu', static function (): MenuItem {
            $user = auth()->user();
            $entry = $user instanceof User && $user->can(AdminPermission::ManageRealms) ? 'admin.realms' : 'admin.users';

            return MenuItem::make(__('navigation.admin'))
                ->href(route($entry, absolute: false))
                ->prefix(Icon::Settings)
                ->can(AdminPermission::Access);
        });
    }
}
