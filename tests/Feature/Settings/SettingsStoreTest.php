<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Settings\Models\Setting;
use App\Shared\Settings\SettingsRegistry;
use Illuminate\Support\Facades\DB;

it('stores and retrieves a free-form setting', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('ui.sidebar', 'collapsed');

    expect($realm->refresh()->settings->get('ui.sidebar'))->toBe('collapsed');
});

it('overwrites an existing key without duplicating rows', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('k', 'a');
    $realm->settings->set('k', 'b');

    expect($realm->refresh()->settings->get('k'))->toBe('b')
        ->and($realm->storedSettings()->where('key', 'k')->count())->toBe(1);
});

it('forgets a key', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('k', 'v');
    $realm->settings->forget('k');

    expect($realm->refresh()->settings->get('k', 'default'))->toBe('default');
});

it('isolates settings per owner and across models', function () {
    $a = Realm::factory()->create();
    $b = Realm::factory()->create();
    $user = User::factory()->create();

    $a->settings->set('shared-key', 'a');
    $b->settings->set('shared-key', 'b');
    $user->settings->set('shared-key', 'u');

    expect($a->refresh()->settings->get('shared-key'))->toBe('a')
        ->and($b->refresh()->settings->get('shared-key'))->toBe('b')
        ->and($user->refresh()->settings->get('shared-key'))->toBe('u');
});

it('casts a declared boolean setting', function () {
    app(SettingsRegistry::class)->define('flags.enabled')->boolean()->default(false);
    $realm = Realm::factory()->create();

    $realm->settings->set('flags.enabled', '1');

    expect($realm->refresh()->settings->get('flags.enabled'))->toBeTrue();
});

it('returns the declared default when a key is unset', function () {
    app(SettingsRegistry::class)->define('flags.enabled')->boolean()->default(true);
    $realm = Realm::factory()->create();

    expect($realm->settings->get('flags.enabled'))->toBeTrue();
});

it('encrypts a declared setting at rest and decrypts on read', function () {
    app(SettingsRegistry::class)->define('integrations.secret')->string()->encrypted();
    $realm = Realm::factory()->create();

    $realm->settings->set('integrations.secret', 'super-secret');

    $raw = $realm->storedSettings()->where('key', 'integrations.secret')->value('value');
    expect($raw)->not->toContain('super-secret')
        ->and($realm->refresh()->settings->get('integrations.secret'))->toBe('super-secret');
});

it('encrypts a declared array setting at rest and decrypts on read', function () {
    app(SettingsRegistry::class)->define('integrations.config')->array()->encrypted();
    $realm = Realm::factory()->create();

    $realm->settings->set('integrations.config', ['token' => 'abc', 'scopes' => ['read', 'write']]);

    $raw = $realm->storedSettings()->where('key', 'integrations.config')->value('value');
    expect($raw)->not->toContain('abc')
        ->and($realm->refresh()->settings->get('integrations.config'))->toBe(['token' => 'abc', 'scopes' => ['read', 'write']]);
});

it('reads settings from the eager-loaded relation without extra queries', function () {
    $realm = Realm::factory()->create();
    $realm->settings->set('a', '1');
    $realm->settings->set('b', '2');

    $fresh = Realm::query()->with('storedSettings')->findOrFail($realm->id);

    DB::enableQueryLog();
    $a = $fresh->settings->get('a');
    $b = $fresh->settings->get('b');
    DB::disableQueryLog();

    expect($a)->toBe('1')
        ->and($b)->toBe('2')
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('purges settings when a hard-deleted owner is removed', function () {
    $user = User::factory()->create();
    $user->settings->set('k', 'v');
    expect($user->storedSettings()->count())->toBe(1);

    $user->delete();

    expect(Setting::query()->where('key', 'k')->exists())->toBeFalse();
});

it('keeps settings when a soft-deletable owner is soft-deleted', function () {
    $realm = Realm::factory()->create();
    $realm->settings->set('k', 'v');

    $realm->delete();

    expect(Setting::query()->where('key', 'k')->exists())->toBeTrue();
});

it('exposes typed getters that coerce and fall back to the given default', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('a.string', 42);
    $realm->settings->set('a.bool', '1');
    $realm->settings->set('a.int', '7');
    $realm->settings->set('a.float', '1.5');
    $realm->settings->set('a.array', ['x']);

    expect($realm->refresh()->settings->string('a.string'))->toBe('42')
        ->and($realm->refresh()->settings->boolean('a.bool'))->toBeTrue()
        ->and($realm->refresh()->settings->integer('a.int'))->toBe(7)
        ->and($realm->refresh()->settings->float('a.float'))->toBe(1.5)
        ->and($realm->refresh()->settings->array('a.array'))->toBe(['x']);
});

it('typed getters return the passed default for an unset key', function () {
    $realm = Realm::factory()->create();

    expect($realm->settings->get('missing', 'fallback'))->toBe('fallback')
        ->and($realm->settings->boolean('missing', true))->toBeTrue()
        ->and($realm->settings->integer('missing', 9))->toBe(9)
        ->and($realm->settings->string('missing', 'x'))->toBe('x');
});

it('typed getters honour a registered definition default over the getter default', function () {
    app(SettingsRegistry::class)->define('flags.on')->boolean()->default(true);
    $realm = Realm::factory()->create();

    expect($realm->settings->boolean('flags.on', false))->toBeTrue();
});
