<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Settings\SettingsRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);
    }

    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(self::factoryNameFor(...));

        $this->configureDefaults();
        $this->registerSettings();
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

        DevCommands::artisan('reverb:start', 'reverb');
        DevCommands::except('server');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    private function registerSettings(): void
    {
        $registry = $this->app->make(SettingsRegistry::class);

        $registry->define('notifications.realm-invitations')->boolean()->default(true);
        $registry->define('notifications.realm-membership')->boolean()->default(true);
    }
}
