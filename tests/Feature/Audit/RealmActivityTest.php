<?php

declare(strict_types=1);

use App\Audit\Ui\Tables\RealmActivityTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Models\Activity;
use App\Shared\Auth\Enums\UserAuditEvent;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Realms\Enums\RealmAuditEvent;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

test('the realm activity table lists only that realm and leaves the instance trail out', function () {
    Audit::record(RealmAuditEvent::RealmUpdated, $this->realm, $this->realm);
    Audit::record(RealmAuditEvent::RealmUpdated, $this->other, $this->other);
    Audit::record(UserAuditEvent::UserBlocked, User::factory()->for($this->realm)->create());

    $this->actingAs(globalAdmin());
    $this->get('/admin/realms/acme/activity')->assertOk();

    $rows = $this->loadTable(RealmActivityTable::class, context: ['realm' => 'acme'])->assertOk()->json('data');
    $expected = Activity::forRealm($this->realm)->sole()->id;

    expect(collect(is_array($rows) ? $rows : [])->pluck('id')->all())->toBe([$expected]);
});

test('the realm activity console is forbidden without realms-view', function () {
    $this->actingAs(globalAdminWith(Scope::UsersRead));

    $this->get('/admin/realms/acme/activity')->assertForbidden();
    $this->loadDeniedTable(RealmActivityTable::class, context: ['realm' => 'acme'])->assertForbidden();
});
