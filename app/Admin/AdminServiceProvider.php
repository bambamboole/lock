<?php

declare(strict_types=1);

namespace App\Admin;

use App\Admin\Actions\EnsureAdministrator;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Admin\Contracts\EnsuresAdministrator;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
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
        /*
         * Every realm definition gates itself on an admin permission, so the
         * resolver is a plain lookup.
         */
        Lattice::context(
            'realm',
            fn (string $value): Realm => Realm::where('slug', $value)->firstOrFail(),
            keyBy: fn (Realm $realm): string => $realm->slug,
        );

        Lattice::context('client', fn (string $value): Client => Client::query()->findOrFail($value));
        Lattice::context('realmRole', fn (string $value): RealmRole => RealmRole::query()->findOrFail($value));
        Lattice::context('resource', fn (string $value): Resource => Resource::query()->findOrFail($value));
        Lattice::context('resourceScope', fn (string $value): ResourceScope => ResourceScope::query()->findOrFail($value));

        Gate::before(function (User $user, string $ability): ?bool {
            $permission = AdminPermission::tryFrom($ability);

            return $permission === null ? null : $user->hasGlobalPermission($permission);
        });

        Lattice::extend('app.user-menu', static fn (): MenuItem => MenuItem::make(__('navigation.admin'))
            ->href(route('admin.realms', absolute: false))
            ->prefix(Icon::Settings)
            ->can(AdminPermission::Access));
    }
}
