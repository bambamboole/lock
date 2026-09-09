<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Actions\CreateNewUser;
use App\Auth\Actions\ResetUserPassword;
use App\Auth\Models\User;
use App\Auth\Support\PermissionRepository;
use App\Auth\Support\RealmPasswordBrokerManager;
use App\Auth\Support\RealmUserProvider;
use App\Auth\Ui\Pages\DevLoginPage;
use App\Shared\Auth\Contracts\PermissionCatalog;
use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginView;
use Bambamboole\LaravelOidc\Server\Users\UserActionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * laravel-oidc-ui binds LoginView in its own register(), and app providers
     * register after package providers, so rebinding here wins.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionRepository::class);
        $this->app->alias(PermissionRepository::class, PermissionCatalog::class);

        if ($this->app->environment('local')) {
            $this->app->bind(LoginView::class, DevLoginPage::class);
        }
    }

    /**
     * Registers against the OIDC singletons directly instead of the Oidc facade:
     * resolving the facade root pulls the encrypter into provider boot, which
     * breaks keyless artisan runs like package:discover on a fresh checkout.
     *
     * The auth views themselves come from laravel-oidc-ui, which binds every
     * view contract in its register() phase.
     */
    public function boot(): void
    {
        /*
         * Not register(): ProviderRepository re-adds the deferred services
         * after the eager providers have run, so a marker dropped there is put
         * straight back and PasswordResetServiceProvider wins the rebinding.
         */
        $this->app->removeDeferredServices(['auth.password', 'auth.password.broker']);
        $this->app->singleton('auth.password', fn ($app): RealmPasswordBrokerManager => new RealmPasswordBrokerManager($app));
        $this->app->singleton('auth.password.broker', fn ($app) => $app->make('auth.password')->broker());

        Auth::provider('realm-eloquent', fn ($app, array $config): RealmUserProvider => new RealmUserProvider(
            $app['hash'],
            $config['model'],
        ));

        Lattice::context('user', User::class);

        Gate::before(function (User $user, string $ability): ?bool {
            $permission = $this->app->make(PermissionRepository::class)->find($ability);

            return $permission !== null && $permission->global
                ? $user->hasGlobalPermission($permission->case)
                : null;
        });

        $userActions = $this->app->make(UserActionManager::class);
        $userActions->createUsersUsing(CreateNewUser::class);
        $userActions->resetUserPasswordsUsing(ResetUserPassword::class);
    }
}
