<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Auth\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;

use function visit;

/**
 * Opens the account page the way a realm user reaches it: signed in on the
 * identity guard, below their own realm.
 */
function visitAccountAs(User $user): PendingAwaitablePage
{
    runningTestCase()->actingAs($user, 'identity');

    return visit(route('account', ['realm' => $user->realm->slug], absolute: false));
}
