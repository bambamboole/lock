<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('migrate', ['--path' => 'database/data-migrations', '--force' => true]);

        $master = $this->firstOrCreateRealm(config('realm.master_slug'), 'Master');
        $this->firstOrCreateRealm(config('realm.default_slug'), 'Default');

        User::factory()->for($master)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(GlobalRoleSeeder::class);

        Artisan::call('admin:grant', ['email' => 'test@example.com']);

        $this->ensureSelfSsoClient();
    }

    private function firstOrCreateRealm(string $slug, string $name): Realm
    {
        return Realm::firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    /**
     * Re-seeding a fresh database wipes the oidc_clients table while .env still
     * points at the deleted first-party client, breaking self-SSO login. Provision
     * a fresh client (and rewrite .env) whenever the configured one is gone.
     */
    private function ensureSelfSsoClient(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $clientId = config('oidc.first_party.client_id');

        if (is_string($clientId) && app(ClientRepository::class)->find($clientId) !== null) {
            return;
        }

        if (Artisan::call('oidc:install-self', ['--fresh' => true, '--force' => true]) !== 0) {
            throw new RuntimeException('Failed to provision the self-SSO client: '.trim(Artisan::output()));
        }
    }
}
