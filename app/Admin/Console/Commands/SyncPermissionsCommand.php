<?php

declare(strict_types=1);

namespace App\Admin\Console\Commands;

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Re-sync the protected global role grants.')]
#[Signature('permission:sync')]
final class SyncPermissionsCommand extends Command
{
    public function handle(): int
    {
        new GlobalRoleSeeder()->run();
        $this->info('Re-synced the protected global role grants.');

        return self::SUCCESS;
    }
}
