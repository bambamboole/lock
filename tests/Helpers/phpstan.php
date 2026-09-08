<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Pest\TestSuite;
use RuntimeException;
use Tests\TestCase;

/**
 * The running test case. Pest's `test()` hands back a proxy typed as
 * `HigherOrderTapProxy|TestCall`, so a helper function outside a test closure
 * cannot call test-case methods on it without losing every type.
 */
function runningTestCase(): TestCase
{
    $test = TestSuite::getInstance()->test;

    if (! $test instanceof TestCase) {
        throw new RuntimeException('No Tests\TestCase is currently running.');
    }

    return $test;
}

/**
 * Narrows a value known to be non-null in context — a nullable relation right
 * after the write that set it, a lookup whose row the test just created — for
 * PHPStan, without weakening the property or return type itself. Throws if the
 * assumption is ever wrong instead of silently continuing. Deliberately not
 * named like a PHPUnit assertion: it is a narrowing assumption, not an
 * expectation the test is making.
 *
 * @template T
 *
 * @param  T  $value
 *
 * @phpstan-assert !null $value
 */
function assumeNotNull(mixed $value, string $message = 'Expected a non-null value.'): void
{
    if ($value === null) {
        throw new RuntimeException($message);
    }
}
