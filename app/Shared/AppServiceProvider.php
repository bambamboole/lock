<?php
declare(strict_types=1);

namespace App\Shared;

use App\Realms\Models\Realm;
use App\Shared\Settings\SettingsRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);

        $this->configureOidc();
    }

    /**
     * The console is its own relying party against the master realm, so the
     * package reads both from Lock's configuration unless OIDC_RP_* points the
     * console at another provider.
     */
    private function configureOidc(): void
    {
        config([
            'oidc.realm' => config('lock.master_realm'),
            'oidc-client.client_id' => config('oidc-client.client_id') ?: config('lock.console_client.id'),
            'oidc-client.client_secret' => config('oidc-client.client_secret') ?: config('lock.console_client.secret'),
        ]);
    }

    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(self::factoryNameFor(...));

        $this->configureDefaults();
    }

    /**
     * @param  class-string<Model>  $modelName
     * @return class-string<Factory<Model>>
     */
    private static function factoryNameFor(string $modelName): string
    {
        $factoryName = 'Database\\Factories\\'.class_basename($modelName).'Factory';

        if (! is_subclass_of($factoryName, Factory::class)) {
            throw new RuntimeException("Model [{$modelName}] has no factory at [{$factoryName}].");
        }

        return $factoryName;
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::shouldBeStrict(! app()->isProduction());

        DevCommands::except('server');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }
}
