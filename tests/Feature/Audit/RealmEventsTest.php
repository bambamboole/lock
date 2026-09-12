<?php

declare(strict_types=1);

use App\Audit\Ui\Fragments\AuditEventContextFragment;
use App\Audit\Ui\Tables\RealmAuditEventsTable;
use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AuditEventType;
use App\Shared\Audit\Models\AuditEvent;
use App\Shared\Authorization\Enums\Scope;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

test('the events table lists only the selected realm', function () {
    $mine = AuditEvent::factory()->for($this->realm)->create();
    AuditEvent::factory()->for($this->other)->create();

    $this->actingAs(globalAdmin());
    $this->get('/admin/realms/acme/events')->assertOk();

    $rows = $this->loadTable(RealmAuditEventsTable::class, context: ['realm' => 'acme'])->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('id')->all())->toBe([$mine->id]);
});

test('the events table filters by category and by failure', function () {
    $failed = AuditEvent::factory()->for($this->realm)->ofType(AuditEventType::LoginFailed)->create();
    AuditEvent::factory()->for($this->realm)->ofType(AuditEventType::LoginSucceeded)->create();
    AuditEvent::factory()->for($this->realm)->ofType(AuditEventType::TokenIssued)->create();

    $this->actingAs(globalAdmin());

    $auth = $this->loadTable(RealmAuditEventsTable::class, ['tf' => ['category' => ['value' => 'auth']]], ['realm' => 'acme'])
        ->assertOk()->json('data');
    $failures = $this->loadTable(RealmAuditEventsTable::class, ['tf' => ['failure' => ['value' => 'true']]], ['realm' => 'acme'])
        ->assertOk()->json('data');

    expect(collect(is_array($auth) ? $auth : [])->count())->toBe(2)
        ->and(collect(is_array($failures) ? $failures : [])->pluck('id')->all())->toBe([$failed->id]);
});

test('a row detail renders the request provenance and the event payload', function () {
    $event = AuditEvent::factory()->for($this->realm)->ofType(AuditEventType::LoginFailed)->create([
        'ip' => '203.0.113.7',
        'client_id' => 'console',
        'context' => ['reason' => 'invalid_credentials'],
    ]);

    $rendered = $this->actingAs(globalAdmin())
        ->loadFragment(AuditEventContextFragment::class, ['realm' => 'acme', 'auditEvent' => $event->id])
        ->assertOk()
        ->json();

    expect(json_encode($rendered))->toContain('203.0.113.7')
        ->and(json_encode($rendered))->toContain('console')
        ->and(json_encode($rendered))->toContain('invalid_credentials');
});

test('a detail from another realm is not readable through the fragment', function () {
    $foreign = AuditEvent::factory()->for($this->other)->create(['ip' => '198.51.100.9']);

    $rendered = $this->actingAs(globalAdmin())
        ->loadFragment(AuditEventContextFragment::class, ['realm' => 'acme', 'auditEvent' => $foreign->id])
        ->assertOk()
        ->json();

    expect(json_encode($rendered))->not->toContain('198.51.100.9')
        ->toContain(__('admin.events.detail.empty'));
});

test('the events console is forbidden without realms-view', function () {
    $this->actingAs(globalAdminWith(Scope::UsersRead));

    $this->get('/admin/realms/acme/events')->assertForbidden();
    $this->loadDeniedTable(RealmAuditEventsTable::class, context: ['realm' => 'acme'])->assertForbidden();
});
