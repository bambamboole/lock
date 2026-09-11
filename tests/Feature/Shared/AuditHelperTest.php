<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;

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

it('names the causing user as the actor, and nobody when nothing caused the row', function () {
    $realm = Realm::factory()->create();
    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm);

    $admin = User::factory()->create();
    $this->actingAs($admin);
    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm);

    expect(Activity::query()->with('causer')->oldest('id')->get()->map(fn (Activity $activity): string => $activity->causer_name)->all())->toBe(['—', $admin->name]);
});
