<?php

declare(strict_types=1);

namespace App\Admin\Console\Commands;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Shared\Auth\Contracts\PermissionCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Rebuild the permission catalog cache and re-sync the protected global role grants.')]
#[Signature('permission:sync')]
final class SyncPermissionsCommand extends Command
{
    public function handle(PermissionCatalog $catalog): int
    {
        $count = $catalog->rebuild();
        $this->info("Rebuilt the permission catalog ({$count} permissions).");

        new GlobalRoleSeeder()->run();
        $this->info('Re-synced the protected global role grants.');

        return self::SUCCESS;
    }
}
