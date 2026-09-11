<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Actions\CreateNewUser;
use App\Auth\Actions\DeleteUser;
use App\Auth\Actions\EndUserSessions;
use App\Auth\Actions\ResetUserMfa;
use App\Auth\Actions\ResetUserPassword;
use App\Auth\Models\User;
use App\Auth\Support\DatabaseRealmRepository;
use App\Auth\Support\DatabaseRealmResolver;
use App\Auth\Support\RealmPasswordBrokerManager;
use App\Auth\Support\RealmRolesTokenClaim;
use App\Auth\Support\RealmUserProvider;
use App\Auth\Support\UserClaimsResolver;
use App\Auth\Ui\Pages\DevLoginPage;
use App\Shared\Auth\Contracts\DeletesUser;
use App\Shared\Auth\Contracts\EndsUserSessions;
use App\Shared\Auth\Contracts\ResetsUserMfa;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\LoginApi;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\LoginEvent;
use Bambamboole\LaravelOidc\Server\Authentication\Pipeline\PostLoginPipeline;
use Bambamboole\LaravelOidc\Server\Authentication\Views\LoginView;
use Bambamboole\LaravelOidc\Server\Scopes\Contracts\ClaimsResolver;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Bambamboole\LaravelOidc\Server\Shared\Users\CreateUser;
use Bambamboole\LaravelOidc\Server\Shared\Users\ResetUserPassword as ResetUserPasswordContract;
use Bambamboole\LaravelOidc\Server\Tokens\Pipeline\AccessTokenPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Package providers register first, so rebinding laravel-oidc-ui's
     * LoginView here wins.
     */
    public function register(): void
    {
        $this->app->singleton(RealmRepository::class, DatabaseRealmRepository::class);
        $this->app->singleton(RealmResolver::class, DatabaseRealmResolver::class);
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

        /*
         * The user provider already hides blocked users; this catches the
         * logins that resolve the user another way (a passkey, a social
         * account, a registration).
         */
        $this->app->make(PostLoginPipeline::class)->register(function (LoginEvent $event, LoginApi $api): void {
            if ($event->user instanceof User && $event->user->isBlocked()) {
                $api->deny('blocked');
            }
        });

        RealmRolesTokenClaim::register($this->app->make(AccessTokenPipeline::class));
    }
}
