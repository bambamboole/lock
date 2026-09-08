<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Enums\AdminPermission;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Enums\Icon;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Lattice::extend('app.user-menu', static fn (): MenuItem => MenuItem::make(__('navigation.admin'))
            ->href(route('admin.dashboard', absolute: false))
            ->prefix(Icon::Settings)
            ->can(AdminPermission::Access));
    }
}
