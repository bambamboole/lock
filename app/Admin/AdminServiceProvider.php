<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Actions\EnsureAdministrator;
use App\Auth\Models\User;
use App\Shared\Admin\Contracts\EnsuresAdministrator;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Auth\Support\RequestUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Ui\Components\MenuItem;
use Lattice\Ui\Enums\Icon;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EnsuresAdministrator::class, EnsureAdministrator::class);
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            $scope = ApiScope::tryFrom($ability);

            return $scope === null ? null : $user->hasManagementScope($scope);
        });

        Lattice::extend('app.user-menu', static fn (): MenuItem => MenuItem::make(__('navigation.admin'))
            ->href(route('admin.realms', absolute: false))
            ->prefix(Icon::Settings)
            ->visible(RequestUser::current()?->managementScopes()->isNotEmpty() ?? false));
    }
}
