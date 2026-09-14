<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lock\Server\Support\Setup\EnvironmentFile;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('migrate', ['--path' => 'database/data-migrations', '--force' => true]);

        User::factory()->for(Realm::master())->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(GlobalRoleSeeder::class);

        Artisan::call('admin:grant', ['email' => 'test@example.com']);

        Realm::master()->runAsCurrent(fn () => $this->callOrFail('oidc:rotate-keys', ['--if-missing' => true]));

        $this->ensureConsoleClientCredentials();
        $this->callOrFail('app:bootstrap');
    }

    /**
     * A local setup gets console credentials of its own, written to `.env` so
     * the next request signs in with them; a deploy brings its own.
     */
    private function ensureConsoleClientCredentials(): void
    {
        if (filled(config('lock.console_client.id')) && filled(config('lock.console_client.secret'))) {
            return;
        }

        $credentials = ['id' => 'lock-console', 'secret' => Str::random(40)];

        app(EnvironmentFile::class)->write([
            'LOCK_CONSOLE_CLIENT_ID' => $credentials['id'],
            'LOCK_CONSOLE_CLIENT_SECRET' => $credentials['secret'],
        ]);

        config(['lock.console_client' => $credentials]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function callOrFail(string $command, array $parameters = []): void
    {
        if (Artisan::call($command, $parameters) !== 0) {
            throw new RuntimeException(trim(Artisan::output()));
        }
    }
}
