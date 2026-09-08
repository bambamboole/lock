<?php
declare(strict_types=1);

namespace App\Auth\Console\Commands;

use App\Admin\Support\GlobalRoles;
use App\Auth\Support\PermissionRepository;
use App\Realms\Models\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Description('Rebuild the permission catalog cache and re-sync the protected global role grants.')]
#[Signature('permission:sync')]
final class SyncPermissionsCommand extends Command
{
    public function handle(): int
    {
        Cache::forget(PermissionRepository::CACHE_KEY);

        // A fresh instance bypasses the singleton's in-memory catalog, so this
        // rediscovers the enums and re-primes the forever cache.
        $count = count(new PermissionRepository()->values());
        $this->info("Rebuilt the permission catalog ({$count} permissions).");

        // firstOrCreate, not sole(): on a freshly provisioned environment the
        // deploy runs before anything has seeded the protected roles.
        DB::transaction(function (): void {
            foreach (GlobalRoles::grants() as $name => $permissions) {
                Role::query()
                    ->firstOrCreate(['realm_id' => null, 'name' => $name])
                    ->syncPermissions($permissions);
            }
        });
        $this->info('Re-synced the protected global role grants.');

        return self::SUCCESS;
    }
}
