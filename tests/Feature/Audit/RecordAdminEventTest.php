<?php
declare(strict_types=1);

use App\Audit\Models\AdminEvent;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Enums\AdminEventCategory;
use App\Shared\Realms\Enums\RealmAdminEvent;
use Illuminate\Http\Request;

test('it records the actor, the subject, the realm and the request provenance', function () {
    $user = User::factory()->create();
    $realm = Realm::factory()->create();
    $this->actingAs($user);

    $this->app->instance('request', Request::create('/admin', server: [
        'REMOTE_ADDR' => '203.0.113.7',
        'HTTP_USER_AGENT' => 'Lock/1.0',
    ]));

    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm, ['name' => ['from' => 'Old', 'to' => 'New']]);

    $event = AdminEvent::query()->sole();

    expect($event->type)->toBe('realm.updated')
        ->and($event->category)->toBe(AdminEventCategory::Realm->value)
        ->and($event->realm_id)->toBe($realm->id)
        ->and($event->user_id)->toBe($user->id)
        ->and($event->subject_id)->toBe($realm->id)
        ->and($event->subject_type)->toBe($realm->getMorphClass())
        ->and($event->ip)->toBe('203.0.113.7')
        ->and($event->user_agent)->toBe('Lock/1.0')
        ->and($event->context)->toBe(['name' => ['from' => 'Old', 'to' => 'New']]);
});

test('it translates the type at read time, in the viewer locale', function () {
    $realm = Realm::factory()->create();

    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm);

    $event = AdminEvent::query()->sole();
    expect($event->type_label)->toBe('Realm updated');

    app()->setLocale('de');
    expect($event->type_label)->toBe('Realm aktualisiert');
});

test('it names the acting administrator, and nobody when nothing acted', function () {
    $realm = Realm::factory()->create();
    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm);

    $admin = User::factory()->create();
    $this->actingAs($admin);
    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm);

    expect(AdminEvent::query()->whereNull('user_id')->sole()->actor_name)->toBe('—')
        ->and(AdminEvent::query()->where('user_id', $admin->id)->sole()->actor_name)->toBe($admin->name);
});

test('nothing is recorded inside withoutRecording', function () {
    $realm = Realm::factory()->create();

    Audit::withoutRecording(fn () => Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm));

    expect(AdminEvent::query()->count())->toBe(0);
});
