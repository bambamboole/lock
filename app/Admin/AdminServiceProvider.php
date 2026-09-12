<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Actions\EnsureAdministrator;
use App\Admin\Ui\Pages\ActivityPage;
use App\Admin\Ui\Pages\ApiReferencePage;
use App\Admin\Ui\Pages\RealmActivityPage;
use App\Admin\Ui\Pages\RealmEventsPage;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Admin\Contracts\EnsuresAdministrator;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Auth\Support\RequestUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Support\Affix;
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
        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RealmEventsPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.events'))
                ->prefix(Affix::icon('shield-alert'))
                ->can(ApiScope::ActivityRead),
            priority: 50,
        );

        Lattice::extend(
            'admin.sidebar.realm',
            static fn (Realm $realm): MenuItem => MenuItem::fromPage(RealmActivityPage::class, ['realm' => $realm->slug])
                ->label(__('navigation.activity'))
                ->prefix(Icon::Clock)
                ->can(ApiScope::ActivityRead),
            priority: 60,
        );

        Lattice::extend(
            'admin.sidebar.instance',
            static fn (): MenuItem => MenuItem::fromPage(ApiReferencePage::class)
                ->label(__('navigation.api'))
                ->prefix(Icon::CodeXml)
                ->can(ApiScope::RealmsRead),
            priority: 20,
        );

        Lattice::extend(
            'admin.sidebar.instance',
            static fn (): MenuItem => MenuItem::fromPage(ActivityPage::class)
                ->label(__('navigation.activity'))
                ->prefix(Icon::Clock)
                ->can(ApiScope::ActivityRead),
            priority: 30,
        );

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
