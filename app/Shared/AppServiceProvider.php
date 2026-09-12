<?php
declare(strict_types=1);

namespace App\Shared;

use App\Realms\Models\Realm;
use App\Shared\Auth\Support\RequestUser;
use App\Shared\Settings\SettingsRegistry;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Spatie\Activitylog\Support\CauserResolver;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);

        $this->configureOidc();
        $this->configureAuditCauser();

        // The reference the console renders is the only one; Scramble's own
        // `docs/api` pages would be a second, unstyled copy of it.
        Scramble::ignoreDefaultRoutes();
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

    /**
     * activitylog resolves the causer from the default guard and types it
     * `?Model`, so a `client_credentials` token — whose principal is a
     * ClientPrincipal, not a model — turns every audited write into a TypeError.
     * The resolver is a scoped binding, so the override is applied on every
     * resolution rather than configured once at boot.
     */
    private function configureAuditCauser(): void
    {
        $this->app->extend(CauserResolver::class, fn (CauserResolver $resolver): CauserResolver => $resolver->resolveUsing(
            fn (mixed $subject): ?Model => $subject instanceof Model ? $subject : RequestUser::current(),
        ));
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
