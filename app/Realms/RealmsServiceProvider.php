<?php

declare(strict_types=1);

namespace App\Realms;

use App\Realms\Actions\CheckRealmDomain;
use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Actions\UpdateRealm;
use App\Realms\Actions\UpdateRealmDomain;
use App\Realms\Models\Realm;
use App\Realms\Support\RealmHostGate;
use App\Shared\Realms\Contracts\ChecksRealmDomain;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Contracts\UpdatesRealm;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;

final class RealmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesRealm::class, CreateRealm::class);
        $this->app->bind(UpdatesRealm::class, UpdateRealm::class);
        $this->app->bind(UpdatesRealmDomain::class, UpdateRealmDomain::class);
        $this->app->bind(ChecksRealmDomain::class, CheckRealmDomain::class);
        $this->app->bind(DeletesRealm::class, DeleteRealm::class);
    }

    public function boot(): void
    {
        /*
         * Every realm definition gates itself on a management scope, so the
         * resolver is a plain lookup.
         */
        Lattice::context(
            'realm',
            fn (string $value): Realm => Realm::where('slug', $value)->firstOrFail(),
            keyBy: fn (Realm $realm): string => $realm->slug,
        );

        Route::matched(fn (RouteMatched $event) => app(RealmHostGate::class)->guard($event->request, $event->route));
    }
}
