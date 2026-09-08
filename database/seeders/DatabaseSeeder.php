<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('migrate', ['--path' => 'database/data-migrations', '--force' => true]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedPersonalAccessClient();

        $this->call(GlobalRoleSeeder::class);

        Artisan::call('admin:grant', ['email' => 'test@example.com']);

        $this->ensureSelfSsoClient();
    }

    /**
     * Re-seeding a fresh database wipes the oauth_clients table while .env still
     * points at the deleted first-party client, breaking self-SSO login. Provision
     * a fresh client (and rewrite .env) whenever the configured one is gone.
     */
    private function ensureSelfSsoClient(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $clientId = config('oidc.first_party.client_id');

        if (is_string($clientId) && Passport::client()->newQuery()->whereKey($clientId)->exists()) {
            return;
        }

        if (Artisan::call('oidc:install-self', ['--fresh' => true, '--force' => true]) !== 0) {
            throw new RuntimeException('Failed to provision the self-SSO client: '.trim(Artisan::output()));
        }
    }

    /**
     * Passport requires a personal access client for the `users` provider before
     * any personal access token can be issued. Create one if it does not yet exist.
     */
    private function seedPersonalAccessClient(): void
    {
        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient('users');

            return;
        } catch (RuntimeException) {
            // No personal access client exists yet — create one below.
        }

        $clients->createPersonalAccessGrantClient(
            config('app.name').' Personal Access Client',
            'users',
        );
    }
}
