<?php
declare(strict_types=1);

namespace Tests;

use Bambamboole\LaravelOidc\Server\Testing\InteractsWithOidc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Lattice\Support\Testing\InteractsWithLatticeComponents;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithLatticeComponents;
    use InteractsWithOidc;
    use RefreshDatabase {
        migrateDatabases as private baseMigrateDatabases;
    }

    /**
     * Rendering a real route resolves @vite, which throws without
     * public/build/manifest.json. Feature tests assert Inertia props and the
     * Lattice component tree, never the Blade shell's asset tags, so they run
     * against a stub and need no build — that is what keeps them out of the
     * node toolchain in CI. Only BrowserTestCase turns this off.
     */
    protected bool $stubsVite = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installSigningKey();

        if ($this->stubsVite) {
            $this->withoutVite();
        }
    }

    /**
     * Runs the data migrations once per process, inside RefreshDatabase's
     * migrated-guard and before the per-test transaction opens — so their rows
     * live in the migrated template database instead of being replayed and
     * rolled back around every single test.
     */
    protected function migrateDatabases(): void
    {
        $this->baseMigrateDatabases();

        $this->artisan('migrate', ['--path' => 'database/data-migrations'])->run();
    }
}
