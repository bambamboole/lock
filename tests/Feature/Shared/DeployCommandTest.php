<?php
declare(strict_types=1);

use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Auth\Support\PermissionRepository;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\SigningKeys\Models\SigningKey;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Lattice\Core\Discovery\DiscoveryManifest;

it('runs every deploy step and leaves the app state primed', function () {
    Realm::factory()->create(['slug' => 'partners']);
    SigningKey::query()->delete();

    try {
        $this->artisan('app:deploy')->assertSuccessful();

        expect(Cache::has(PermissionRepository::CACHE_KEY))->toBeTrue()
            ->and(SigningKey::query()->whereNull('retired_at')->pluck('realm_id')->all())
            ->toEqualCanonicalizing(['admin', 'partners'])
            ->and(Role::query()->pluck('name')->all())
            ->toEqualCanonicalizing([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT])
            ->and(app(DiscoveryManifest::class)->isCached())->toBeTrue();
    } finally {
        // The discovery manifest is a real file under bootstrap/cache — left
        // behind it would freeze local discovery long after the suite ran.
        Artisan::call('lattice:discover-clear');
    }
});
