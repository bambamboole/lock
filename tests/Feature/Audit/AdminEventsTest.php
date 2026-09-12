<?php

declare(strict_types=1);

use App\Audit\Models\AdminEvent;
use App\Audit\Ui\Fragments\InstanceAdminEventContextFragment;
use App\Audit\Ui\Tables\InstanceAdminEventsTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Enums\UserAdminEvent;
use App\Shared\Realms\Enums\RealmAdminEvent;

use function Tests\Helpers\globalAdmin;

test('any admin can read the admin events trail', function (string $roleName) {
    $admin = globalAdmin($roleName);

    $this->actingAs($admin)->get('/admin/events')->assertOk();
    $this->actingAs($admin)->loadTable(InstanceAdminEventsTable::class)->assertOk();
})->with([ManagementRoles::SUPER_ADMIN]);

test('a non-admin cannot read the admin events trail', function () {
    $this->actingAs(User::factory()->create())->get('/admin/events')->assertForbidden();
    $this->actingAs(User::factory()->create())->loadDeniedTable(InstanceAdminEventsTable::class)->assertForbidden();
});

test('the instance table shows instance rows and hides realm rows', function () {
    $admin = globalAdmin();
    $realm = Realm::factory()->create();

    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm);
    Audit::record(UserAdminEvent::SuperAdminGranted, User::factory()->create());

    $rows = $this->actingAs($admin)->loadTable(InstanceAdminEventsTable::class)->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('type')->all())->toBe(['user.super-admin-granted']);
});

test('the row detail renders the recorded context for an admin', function () {
    $admin = globalAdmin();
    Audit::record(UserAdminEvent::SuperAdminGranted, User::factory()->create(), context: ['added' => ['Super Admin']]);

    $event = AdminEvent::global()->sole();

    $this->actingAs($admin)
        ->loadFragment(InstanceAdminEventContextFragment::class, ['adminEvent' => $event->id])
        ->assertOk()
        ->assertSee('Super Admin');
});

test('a non-admin cannot read an admin event detail', function () {
    $this->actingAs(User::factory()->create())
        ->loadDeniedFragment(InstanceAdminEventContextFragment::class, ['adminEvent' => 'missing'])
        ->assertForbidden();
});

test('a realm-scoped row is not readable through the instance fragment', function () {
    $admin = globalAdmin();
    $realm = Realm::factory()->create();

    Audit::record(RealmAdminEvent::RealmUpdated, $realm, $realm, ['secret' => 'realm-only-sentinel']);

    $event = AdminEvent::query()->sole();

    $this->actingAs($admin)
        ->loadFragment(InstanceAdminEventContextFragment::class, ['adminEvent' => $event->id])
        ->assertOk()
        ->assertDontSee('realm-only-sentinel')
        ->assertSee(__('admin.events.detail.empty'));
});
