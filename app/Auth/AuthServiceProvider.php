<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Actions\CreateNewUser;
use App\Auth\Actions\DeleteUser;
use App\Auth\Actions\EndUserSessions;
use App\Auth\Actions\ResetUserMfa;
use App\Auth\Actions\ResetUserPassword;
use App\Auth\Console\Commands\InstallSelfCommand;
use App\Auth\Models\User;
use App\Auth\Support\DatabaseRealmRepository;
use App\Auth\Support\DatabaseRealmResolver;
use App\Auth\Support\PermissionRepository;
use App\Auth\Support\RealmPasswordBrokerManager;
use App\Auth\Support\RealmRolesTokenClaim;
use App\Auth\Support\RealmUserProvider;
use App\Auth\Support\UserClaimsResolver;
use App\Auth\Ui\Pages\DevLoginPage;
use App\Shared\Auth\Contracts\DeletesUser;
use App\Shared\Auth\Contracts\EndsUserSessions;
use App\Shared\Auth\Contracts\PermissionCatalog;
use App\Shared\Auth\Contracts\ResetsUserMfa;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\LoginApi;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\LoginEvent;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\PostLoginPipeline;
use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginView;
use Bambamboole\LaravelOidc\Server\Installation\Commands\InstallSelfCommand as PackageInstallSelfCommand;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ClaimsResolver;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Bambamboole\LaravelOidc\Server\Shared\Users\CreateUser;
use Bambamboole\LaravelOidc\Server\Shared\Users\ResetUserPassword as ResetUserPasswordContract;
use Bambamboole\LaravelOidc\Server\Tokens\Pipeline\AccessTokenPipeline;
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
        $this->app->singleton(RealmRepository::class, DatabaseRealmRepository::class);
        $this->app->singleton(RealmResolver::class, DatabaseRealmResolver::class);
        $this->app->bind(PackageInstallSelfCommand::class, InstallSelfCommand::class);
        $this->app->bind(CreateUser::class, CreateNewUser::class);
        $this->app->bind(ResetUserPasswordContract::class, ResetUserPassword::class);
        $this->app->bind(DeletesUser::class, DeleteUser::class);
        $this->app->bind(EndsUserSessions::class, EndUserSessions::class);
        $this->app->bind(ResetsUserMfa::class, ResetUserMfa::class);
        $this->app->bind(ClaimsResolver::class, UserClaimsResolver::class);

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

        /*
         * The user provider already hides blocked users from credential and
         * session lookups; this covers the interactive logins that resolve the
         * user another way (a passkey ceremony, a social account, a fresh
         * registration) so they fail before a session is ever established.
         */
        $this->app->make(PostLoginPipeline::class)->register(function (LoginEvent $event, LoginApi $api): void {
            if ($event->user instanceof User && $event->user->isBlocked()) {
                $api->deny('blocked');
            }
        });

        RealmRolesTokenClaim::register($this->app->make(AccessTokenPipeline::class));
    }
}
