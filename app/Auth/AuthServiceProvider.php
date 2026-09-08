<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Actions\CreateNewUser;
use App\Auth\Actions\ResetUserPassword;
use App\Auth\Models\User;
use App\Auth\Support\PermissionRepository;
use App\Auth\Ui\Pages\DevLoginPage;
use App\Shared\Auth\Contracts\PermissionCatalog;
use Bambamboole\LaravelOidc\Server\Auth\UserActionManager;
use Bambamboole\LaravelOidc\Server\Auth\Views\LoginView;
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
