<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use App\Realms\Models\Realm;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The one command a release pipeline runs after the new code is live. Every
 * step is idempotent, and the migrations run isolated so concurrently booting
 * instances do not race each other. Bytecode caches (config, routes, views)
 * stay with the deploy platform — this command owns application state.
 */
#[Description('Run the deploy steps every release needs: migrations, data migrations, signing keys, permission sync, environment bootstrap, lattice discovery cache.')]
#[Signature('app:deploy')]
final class DeployCommand extends Command
{
    public function handle(): int
    {
        $steps = [
            ['migrate', fn (): int => $this->call('migrate', ['--force' => true, '--isolated' => true])],
            ['migrate --path=database/data-migrations', fn (): int => $this->call('migrate', ['--force' => true, '--isolated' => true, '--path' => 'database/data-migrations'])],
            ['oidc:rotate-keys --if-missing', $this->generateMissingSigningKeys(...)],
            ['permission:sync', fn (): int => $this->call('permission:sync')],
            ['app:bootstrap', fn (): int => $this->call('app:bootstrap')],
            ['lattice:discover-cache', fn (): int => $this->call('lattice:discover-cache')],
        ];

        foreach ($steps as [$label, $step]) {
            if ($step() !== self::SUCCESS) {
                $this->components->error("Deploy step [{$label}] failed.");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Signing keys live in `oidc_signing_keys`, one active key per realm, and
     * the command only ever sees the current realm — hence the loop. It runs
     * after the data migrations, which is where the administration realm the
     * first deploy needs a key for is created.
     */
    private function generateMissingSigningKeys(): int
    {
        foreach (Realm::query()->cursor() as $realm) {
            $failed = $realm->runAsCurrent(
                fn (): bool => $this->call('oidc:rotate-keys', ['--if-missing' => true]) !== self::SUCCESS,
            );

            if ($failed === true) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
