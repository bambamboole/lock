<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Actions\SignOutBrowserSession;
use App\Auth\Ui\Tables\BrowserSessionsTable;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $session
 */
function browserSessionRow(User $user, string $id, array $session = [], string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36'): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->getKey(),
        'ip_address' => '203.0.113.7',
        'user_agent' => $userAgent,
        'payload' => sessionPayload($session),
        'last_activity' => now()->subMinutes(5)->getTimestamp(),
    ]);
}

/**
 * @param  array<string, mixed>  $session
 */
function sessionPayload(array $session): string
{
    $handler = new ArraySessionHandler(1);
    $store = new Store('payload', $handler, Str::random(40), config('session.serialization'));
    $store->put($session);
    $store->save();

    $payload = $handler->read($store->getId());

    if ($payload === false) {
        throw new RuntimeException('The session store wrote no payload.');
    }

    return base64_encode($payload);
}

function currentSessionId(): string
{
    return Str::random(40);
}

/**
 * @return array<int, array<string, mixed>>
 */
function browserSessionRows(mixed $test, User $user, string $current): array
{
    return $test->actingAs($user)
        ->withCredentials()
        ->withCookie(config('session.cookie'), $current)
        ->loadTable(BrowserSessionsTable::class)
        ->assertOk()
        ->json('data');
}

it('lists the user browser sessions and marks the current one', function () {
    $user = User::factory()->create();
    $current = currentSessionId();
    browserSessionRow($user, $current);
    browserSessionRow($user, 'other-session', userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1');
    browserSessionRow(User::factory()->create(), 'someone-elses-session');

    $rows = collect(browserSessionRows($this, $user, $current));

    expect($rows->pluck('status', 'id')->all())->toBe([$current => 'current', 'other-session' => 'active'])
        ->and($rows->pluck('device', 'id')->all())->toBe([$current => 'Chrome · macOS', 'other-session' => 'Safari · iPhone']);
});

it('signs out another browser session and revokes the provider session behind it', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($realm)->create();
    $oidcSession = $realm->runAsCurrent(fn (): OidcSession => OidcSession::query()->create([
        'realm_id' => 'acme',
        'user_id' => $user->getKey(),
        'created_at' => now(),
        'expires_at' => now()->addDay(),
    ]));
    browserSessionRow($user, 'other-session', ['oidc' => ['sid' => $oidcSession->sid]]);

    $this->actingAs($user)
        ->withCredentials()
        ->withCookie(config('session.cookie'), currentSessionId())
        ->callAction(SignOutBrowserSession::class, context: ['session' => 'other-session'])
        ->assertOk();

    expect(DB::table('sessions')->where('id', 'other-session')->exists())->toBeFalse()
        ->and(OidcSession::query()->whereKey($oidcSession->sid)->firstOrFail()->revoked_at)->not->toBeNull();
});

it('refuses to sign out the current session or another user session', function () {
    $user = User::factory()->create();
    $current = currentSessionId();
    browserSessionRow($user, $current);
    browserSessionRow(User::factory()->create(), 'someone-elses-session');

    $this->actingAs($user)
        ->withCredentials()
        ->withCookie(config('session.cookie'), $current)
        ->callAction(SignOutBrowserSession::class, context: ['session' => $current])
        ->assertForbidden();

    $this->actingAs($user)
        ->withCredentials()
        ->withCookie(config('session.cookie'), $current)
        ->callAction(SignOutBrowserSession::class, context: ['session' => 'someone-elses-session'])
        ->assertStatus(422);

    expect(DB::table('sessions')->count())->toBe(2);
});
