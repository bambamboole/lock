<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
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

        Realm::master()->runAsCurrent(fn () => $this->ensureSelfSsoClient());
    }

    /**
     * Re-seeding a fresh database wipes the oidc_clients table while .env still
     * points at the deleted first-party client, breaking self-SSO login. Provision
     * a fresh client (and rewrite .env) whenever the configured one is gone.
     */
    private function ensureSelfSsoClient(): void
    {
        if (Artisan::call('oidc:rotate-keys', ['--if-missing' => true, '--no-interaction' => true]) !== 0) {
            throw new RuntimeException('Failed to prepare OIDC signing keys: '.trim(Artisan::output()));
        }

        $clientId = app(RealmResolver::class)->current()->clients()->firstPartyClientId;

        if (is_string($clientId) && Client::query()->inRealm()->where('client_id', $clientId)->exists()) {
            return;
        }

        if (Artisan::call('oidc:install-self', ['--fresh' => true, '--force' => true]) !== 0) {
            throw new RuntimeException('Failed to provision the self-SSO client: '.trim(Artisan::output()));
        }
    }
}
