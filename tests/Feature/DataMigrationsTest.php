<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
 * Pins the wiring, not any single migration's contents: Tests\TestCase applies
 * database/data-migrations once per process before the per-test transaction, so
 * by the time a test body runs every file in the folder is already recorded and
 * a re-run has nothing left to do. A broken file, a renamed folder, or a lost
 * TestCase::migrateDatabases() override all fail here.
 */
it('applies every data migration before a test body runs', function (): void {
    $files = glob(database_path('data-migrations/*_*.php'));

    if ($files === false) {
        throw new RuntimeException('glob() over database/data-migrations failed');
    }

    expect(array_map(fn (string $path): string => basename($path, '.php'), $files))
        ->each->toBeIn(DB::table('migrations')->pluck('migration')->all());

    $this->artisan('migrate', ['--path' => 'database/data-migrations'])
        ->expectsOutputToContain('Nothing to migrate')
        ->assertSuccessful();
});
