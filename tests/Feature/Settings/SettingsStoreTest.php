<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Shared\Settings\Models\Setting;
use App\Shared\Settings\SettingsRegistry;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Facades\DB;

it('stores and retrieves a free-form setting', function () {
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('ui.sidebar', 'collapsed');

    expect($tenant->refresh()->settings->get('ui.sidebar'))->toBe('collapsed');
});

it('overwrites an existing key without duplicating rows', function () {
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('k', 'a');
    $tenant->settings->set('k', 'b');

    expect($tenant->refresh()->settings->get('k'))->toBe('b')
        ->and($tenant->storedSettings()->where('key', 'k')->count())->toBe(1);
});

it('forgets a key', function () {
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('k', 'v');
    $tenant->settings->forget('k');

    expect($tenant->refresh()->settings->get('k', 'default'))->toBe('default');
});

it('isolates settings per owner and across models', function () {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();
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
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('flags.enabled', '1');

    expect($tenant->refresh()->settings->get('flags.enabled'))->toBeTrue();
});

it('returns the declared default when a key is unset', function () {
    app(SettingsRegistry::class)->define('flags.enabled')->boolean()->default(true);
    $tenant = Tenant::factory()->create();

    expect($tenant->settings->get('flags.enabled'))->toBeTrue();
});

it('encrypts a declared setting at rest and decrypts on read', function () {
    app(SettingsRegistry::class)->define('integrations.secret')->string()->encrypted();
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('integrations.secret', 'super-secret');

    $raw = $tenant->storedSettings()->where('key', 'integrations.secret')->value('value');
    expect($raw)->not->toContain('super-secret')
        ->and($tenant->refresh()->settings->get('integrations.secret'))->toBe('super-secret');
});

it('encrypts a declared array setting at rest and decrypts on read', function () {
    app(SettingsRegistry::class)->define('integrations.config')->array()->encrypted();
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('integrations.config', ['token' => 'abc', 'scopes' => ['read', 'write']]);

    $raw = $tenant->storedSettings()->where('key', 'integrations.config')->value('value');
    expect($raw)->not->toContain('abc')
        ->and($tenant->refresh()->settings->get('integrations.config'))->toBe(['token' => 'abc', 'scopes' => ['read', 'write']]);
});

it('reads settings from the eager-loaded relation without extra queries', function () {
    $tenant = Tenant::factory()->create();
    $tenant->settings->set('a', '1');
    $tenant->settings->set('b', '2');

    $fresh = Tenant::query()->with('storedSettings')->findOrFail($tenant->id);

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

    expect(Setting::query()->count())->toBe(0);
});

it('keeps settings when a soft-deletable owner is soft-deleted', function () {
    $tenant = Tenant::factory()->create();
    $tenant->settings->set('k', 'v');

    $tenant->delete();

    expect(Setting::query()->count())->toBe(1);
});

it('exposes typed getters that coerce and fall back to the given default', function () {
    $tenant = Tenant::factory()->create();

    $tenant->settings->set('a.string', 42);
    $tenant->settings->set('a.bool', '1');
    $tenant->settings->set('a.int', '7');
    $tenant->settings->set('a.float', '1.5');
    $tenant->settings->set('a.array', ['x']);

    expect($tenant->refresh()->settings->string('a.string'))->toBe('42')
        ->and($tenant->refresh()->settings->boolean('a.bool'))->toBeTrue()
        ->and($tenant->refresh()->settings->integer('a.int'))->toBe(7)
        ->and($tenant->refresh()->settings->float('a.float'))->toBe(1.5)
        ->and($tenant->refresh()->settings->array('a.array'))->toBe(['x']);
});

it('typed getters return the passed default for an unset key', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->settings->get('missing', 'fallback'))->toBe('fallback')
        ->and($tenant->settings->boolean('missing', true))->toBeTrue()
        ->and($tenant->settings->integer('missing', 9))->toBe(9)
        ->and($tenant->settings->string('missing', 'x'))->toBe('x');
});

it('typed getters honour a registered definition default over the getter default', function () {
    app(SettingsRegistry::class)->define('flags.on')->boolean()->default(true);
    $tenant = Tenant::factory()->create();

    expect($tenant->settings->boolean('flags.on', false))->toBeTrue();
});
