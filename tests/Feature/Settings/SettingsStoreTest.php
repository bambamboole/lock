<?php

declare(strict_types=1);

use App\Realms\Models\Realm;
use App\Shared\Settings\Models\Setting;
use App\Shared\Settings\SettingsRegistry;
use Illuminate\Support\Facades\DB;

it('overwrites an existing key without duplicating rows', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('k', 'a');
    $realm->settings->set('k', 'b');

    expect($realm->refresh()->settings->get('k'))->toBe('b')
        ->and($realm->storedSettings()->where('key', 'k')->count())->toBe(1);
});

it('isolates settings per owner', function () {
    $a = Realm::factory()->create();
    $b = Realm::factory()->create();

    $a->settings->set('shared-key', 'a');
    $b->settings->set('shared-key', 'b');

    expect($a->refresh()->settings->get('shared-key'))->toBe('a')
        ->and($b->refresh()->settings->get('shared-key'))->toBe('b');
});

it('casts a declared setting and supplies its default over the getter default', function () {
    app(SettingsRegistry::class)->define('flags.enabled')->boolean()->default(false);
    app(SettingsRegistry::class)->define('flags.on')->boolean()->default(true);
    $realm = Realm::factory()->create();

    $realm->settings->set('flags.enabled', '1');

    expect($realm->refresh()->settings->get('flags.enabled'))->toBeTrue()
        ->and($realm->settings->boolean('flags.on', false))->toBeTrue();
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

it('purges settings when the owner is deleted', function () {
    $realm = Realm::factory()->create();
    $realm->settings->set('k', 'v');

    $realm->delete();

    expect(Setting::query()->where('key', 'k')->exists())->toBeFalse();
});

it('exposes typed getters that coerce and fall back to the given default', function () {
    $realm = Realm::factory()->create();

    $realm->settings->set('a.string', 42);
    $realm->settings->set('a.bool', '1');
    $realm->settings->set('a.int', '7');
    $realm->settings->set('a.array', ['x']);
    $realm->refresh();

    expect($realm->settings->string('a.string'))->toBe('42')
        ->and($realm->settings->boolean('a.bool'))->toBeTrue()
        ->and($realm->settings->integer('a.int'))->toBe(7)
        ->and($realm->settings->array('a.array'))->toBe(['x'])
        ->and($realm->settings->get('missing', 'fallback'))->toBe('fallback')
        ->and($realm->settings->boolean('missing', true))->toBeTrue()
        ->and($realm->settings->integer('missing', 9))->toBe(9)
        ->and($realm->settings->string('missing', 'x'))->toBe('x');
});
