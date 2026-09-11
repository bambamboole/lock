<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Playwright\Playwright;

use function visit;

/**
 * Opens the account page the way a realm user reaches it: signed in on the
 * identity guard, on their own realm's host. The browser server rewrites every
 * visit to its own address, so the realm host travels as the Host header of
 * every request the page makes; BrowserTestCase resets it after the test.
 */
function visitAccountAs(User $user): PendingAwaitablePage
{
    runningTestCase()->actingAs($user, 'identity');

    if (! $user->realm->isMaster()) {
        Playwright::setHost($user->realm->host());
    }

    return visit('/account');
}
