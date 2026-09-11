<?php

declare(strict_types=1);

namespace App\Realms;

use App\Realms\Actions\CheckRealmDomain;
use App\Realms\Actions\CreateClient;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\CreateRealmRole;
use App\Realms\Actions\DeleteClient;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Actions\DeleteRealmRole;
use App\Realms\Actions\ProvisionConsoleClient;
use App\Realms\Actions\RotateClientSecret;
use App\Realms\Actions\SetClientRevocation;
use App\Realms\Actions\SyncUserRealmRoles;
use App\Realms\Actions\UpdateClient;
use App\Realms\Actions\UpdateRealmConfiguration;
use App\Realms\Actions\UpdateRealmDomain;
use App\Realms\Actions\UpdateRealmRole;
use App\Realms\Models\Realm;
use App\Realms\Support\RealmHostGate;
use App\Realms\Support\RealmScopedLatticeEndpoints;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Contracts\CreatesClient;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Contracts\CreatesRealmRole;
use App\Shared\Realms\Contracts\DeletesClient;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Contracts\DeletesRealmRole;
use App\Shared\Realms\Contracts\ProvisionsConsoleClient;
use App\Shared\Realms\Contracts\RotatesClientSecret;
use App\Shared\Realms\Contracts\SetsClientRevocation;
use App\Shared\Realms\Contracts\SyncsUserRealmRoles;
use App\Shared\Realms\Contracts\UpdatesClient;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use App\Shared\Realms\Contracts\UpdatesRealmRole;
use App\Shared\Realms\Support\RealmConfiguration;
use App\Shared\Realms\Support\RealmRoutes;
use App\Shared\Settings\SettingsRegistry;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class RealmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesRealm::class, CreateRealm::class);
        $this->app->bind(UpdatesRealmConfiguration::class, UpdateRealmConfiguration::class);
        $this->app->bind(UpdatesRealmDomain::class, UpdateRealmDomain::class);
        $this->app->bind(ChecksRealmDomain::class, CheckRealmDomain::class);
        $this->app->bind(DeletesRealm::class, DeleteRealm::class);
        $this->app->bind(CreatesClient::class, CreateClient::class);
        $this->app->bind(ProvisionsConsoleClient::class, ProvisionConsoleClient::class);
        $this->app->bind(UpdatesClient::class, UpdateClient::class);
        $this->app->bind(RotatesClientSecret::class, RotateClientSecret::class);
        $this->app->bind(SetsClientRevocation::class, SetClientRevocation::class);
        $this->app->bind(DeletesClient::class, DeleteClient::class);
        $this->app->bind(CreatesRealmRole::class, CreateRealmRole::class);
        $this->app->bind(UpdatesRealmRole::class, UpdateRealmRole::class);
        $this->app->bind(DeletesRealmRole::class, DeleteRealmRole::class);
        $this->app->bind(SyncsUserRealmRoles::class, SyncUserRealmRoles::class);
    }

    public function boot(): void
    {
        RealmConfiguration::register($this->app->make(SettingsRegistry::class));

        URL::formatPathUsing(fn (string $path): string => $this->app->make(RealmScopedLatticeEndpoints::class)->format($path));

        // A link to a realm's own pages — a password reset an administrator
        // sends, a verification mail a queued job renders — has to point at
        // that realm's host, not at the host the URL happens to be minted on.
        URL::formatHostUsing(fn (string $root, mixed $route): string => $route instanceof RouteDefinition && $route->named(...RealmRoutes::SERVED)
            ? (Realm::currentOrigin() ?? $root)
            : $root);

        Route::matched(fn (RouteMatched $event) => app(RealmHostGate::class)->guard($event->request, $event->route));
    }
}
