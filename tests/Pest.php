<?php

declare(strict_types=1);

use Tests\BrowserTestCase;
use Tests\TestCase;

/*
 * Bindings only. Shared helpers live in tests/Helpers/*.php under the
 * Tests\Helpers namespace (Pest auto-loads that directory) and are imported per
 * file with `use function Tests\Helpers\realmMember;`. A helper defined here
 * would be a global function, and every test file shares one global function
 * table — reusing a name in a second file is a fatal redeclare, not an override.
 *
 * RefreshDatabase is used by Tests\TestCase itself, not applied here: a trait on
 * the class Pest generates per file wins over an inherited method, so re-adding
 * it would shadow TestCase::migrateDatabases() and skip the data migrations.
 * The override's `: void` turns that into a fatal signature clash rather than a
 * silent one.
 */
pest()->extend(TestCase::class)
    ->in('Feature');

/*
 * Browser gets its own base class because it is the one suite that must resolve
 * the real Vite manifest — see Tests\TestCase::$stubsVite.
 */
pest()->extend(BrowserTestCase::class)
    ->in('Browser');
