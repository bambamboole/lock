<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AuditEventCategory;
use App\Shared\Audit\Enums\AuditEventType;
use App\Shared\Audit\Models\AuditEvent;
use Bambamboole\LaravelOidc\Server\Authentication\Events\LoginFailed;
use Bambamboole\LaravelOidc\Server\Authentication\Events\LoginSucceeded;
use Bambamboole\LaravelOidc\Server\SigningKeys\Events\KeysRotated;

test('a security event raised inside a realm is recorded against that realm', function () {
    $realm = Realm::factory()->create();
    $user = User::factory()->for($realm)->create();

    $realm->runAsCurrent(fn () => event(new LoginSucceeded($user->id, ['pwd'])));

    $event = AuditEvent::query()->sole();

    expect($event->type)->toBe(AuditEventType::LoginSucceeded->value)
        ->and($event->category)->toBe(AuditEventCategory::Auth->value)
        ->and($event->realm_id)->toBe($realm->id)
        ->and($event->user_id)->toBe($user->id)
        ->and($event->failure)->toBeFalse()
        ->and($event->context)->toBe(['amr' => ['pwd']]);
});

test('a failed sign-in is recorded with the address that was tried and no user', function () {
    $realm = Realm::factory()->create();

    $realm->runAsCurrent(fn () => event(new LoginFailed(
        method: 'password',
        reason: 'invalid_credentials',
        username: 'ghost@example.com',
    )));

    $event = AuditEvent::query()->sole();

    expect($event->failure)->toBeTrue()
        ->and($event->user_id)->toBeNull()
        ->and($event->actor_name)->toBe('ghost@example.com')
        ->and($event->context['reason'] ?? null)->toBe('invalid_credentials');
});

test('an event raised outside any realm stays instance-wide', function () {
    event(new KeysRotated('RS256'));

    expect(AuditEvent::query()->sole()->realm_id)->toBeNull();
});

test('recording is skipped while auditing is disabled', function () {
    config()->set('oidc.audit.enabled', false);

    Realm::factory()->create()->runAsCurrent(fn () => event(new LoginSucceeded(fake()->uuid(), [])));

    expect(AuditEvent::query()->count())->toBe(0);
});
