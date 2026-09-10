<?php

declare(strict_types=1);

namespace App\Realms;

use App\Realms\Actions\CreateRealm;
use App\Realms\Actions\DeleteRealm;
use App\Realms\Actions\UpdateRealmConfiguration;
use App\Shared\Realms\Contracts\CreatesRealm;
use App\Shared\Realms\Contracts\DeletesRealm;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Support\RealmConfiguration;
use App\Shared\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;

final class RealmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesRealm::class, CreateRealm::class);
        $this->app->bind(UpdatesRealmConfiguration::class, UpdateRealmConfiguration::class);
        $this->app->bind(DeletesRealm::class, DeleteRealm::class);
    }

    public function boot(): void
    {
        RealmConfiguration::register($this->app->make(SettingsRegistry::class));
    }
}
