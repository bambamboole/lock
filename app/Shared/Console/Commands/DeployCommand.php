<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The one command a release pipeline runs after the new code is live. Every
 * step is idempotent, and the migrations run isolated so concurrently booting
 * instances do not race each other. Bytecode caches (config, routes, views)
 * stay with the deploy platform — this command owns application state.
 */
#[Description('Run the deploy steps every release needs: migrations, data migrations, permission sync, lattice discovery cache.')]
#[Signature('app:deploy')]
final class DeployCommand extends Command
{
    public function handle(): int
    {
        $steps = [
            ['migrate', ['--force' => true, '--isolated' => true]],
            ['migrate', ['--force' => true, '--isolated' => true, '--path' => 'database/data-migrations']],
            ['permission:sync', []],
            ['lattice:discover-cache', []],
        ];

        foreach ($steps as [$command, $arguments]) {
            if ($this->call($command, $arguments) !== self::SUCCESS) {
                $this->components->error("Deploy step [{$command}] failed.");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
