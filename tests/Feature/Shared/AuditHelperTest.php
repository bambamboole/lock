<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;

it('scopes activities to a realm', function () {
    $realmA = Realm::factory()->create();
    $realmB = Realm::factory()->create();

    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'a', 'event' => 'x', 'realm_id' => $realmA->id]);
    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'b', 'event' => 'y', 'realm_id' => $realmB->id]);

    expect(Activity::forRealm($realmA)->count())->toBe(1);
});

it('logs a realm-scoped activity with causer, event, subject and realm_id', function () {
    $user = User::factory()->create();
    $realm = Realm::factory()->create();
    $this->actingAs($user);

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['name' => ['Old', 'New']]);

    $activity = Activity::query()->latest('id')->firstOrFail();
    expect($activity->log_name)->toBe('realm')
        ->and($activity->event)->toBe('realm.updated')
        ->and($activity->realm_id)->toBe($realm->id)
        ->and($activity->getRawOriginal('description'))->toBe('audit.events.realm.updated')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->subject_id)->toBe($realm->id)
        ->and($activity->properties?->toArray())->toBe(['name' => ['Old', 'New']]);
});

it('resolves the stored description key in the viewer locale at read time', function () {
    $realm = Realm::factory()->create();

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm);

    $activity = Activity::query()->latest('id')->firstOrFail();
    expect($activity->description)->toBe('Realm updated');

    app()->setLocale('de');
    expect($activity->description)->toBe('Realm aktualisiert');
});

it('leaves a pre-existing raw description untranslated', function () {
    $realm = Realm::factory()->create();

    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'Legacy text', 'event' => 'realm.updated', 'realm_id' => $realm->id]);

    expect(Activity::query()->latest('id')->firstOrFail()->description)->toBe('Legacy text');
});
