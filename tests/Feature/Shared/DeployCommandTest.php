<?php
declare(strict_types=1);

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lattice\Core\Discovery\DiscoveryManifest;

it('runs every deploy step and leaves the app state primed', function () {
    Realm::factory()->create(['slug' => 'partners']);
    SigningKey::query()->delete();
    config([
        'lock.console_client' => ['id' => 'lock-console', 'secret' => 'console-secret'],
        'lock.admin' => ['email' => 'root@lock.test', 'name' => 'Root', 'password' => 'CorrectHorse42'],
    ]);

    try {
        $this->artisan('app:deploy')->assertSuccessful();

        expect(SigningKey::query()->whereNull('retired_at')->pluck('realm_id')->all())
            ->toEqualCanonicalizing(['admin', 'partners'])
            ->and(Role::query()->pluck('name')->all())
            ->toEqualCanonicalizing([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT])
            ->and(Client::query()->where('client_id', 'lock-console')->exists())->toBeTrue()
            ->and(Realm::master()->users()->where('email', 'root@lock.test')->exists())->toBeTrue()
            ->and(app(DiscoveryManifest::class)->isCached())->toBeTrue();
    } finally {
        // The discovery manifest is a real file under bootstrap/cache — left
        // behind it would freeze local discovery long after the suite ran.
        Artisan::call('lattice:discover-clear');
    }
});

it('runs the first deploy with the database cache store before its lock table exists', function () {
    config(['cache.default' => 'database']);
    Schema::drop('cache');
    Schema::drop('cache_locks');
    DB::table('migrations')->where('migration', '0001_01_01_000001_create_cache_table')->delete();

    try {
        $this->artisan('app:deploy')->assertSuccessful();

        expect(Schema::hasTable('cache_locks'))->toBeTrue();
    } finally {
        Artisan::call('lattice:discover-clear');
    }
});
