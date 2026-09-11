<?php

declare(strict_types=1);

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Support\GlobalRoles;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Admin\Ui\Fragments\AdminActivityChangesFragment;
use App\Admin\Ui\Tables\AdminActivityTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Lattice\Core\Attributes\AsFragment;
use Lattice\Core\Contracts\SignsComponentReferences;
use Lattice\Fragments\FragmentRegistry;
use Spatie\Attributes\Attributes;

use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\globalAdmin;

test('a role update that changes nothing is not logged', function () {
    $admin = globalAdmin();
    $target = User::factory()->for(Realm::master())->create();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $target->id])->assertOk();

    expect(Activity::global()->count())->toBe(0);
});

test('any admin can read the activity trail', function (string $roleName) {
    $admin = globalAdmin($roleName);

    $this->actingAs($admin)->get('/admin/activity')->assertOk();
    $this->actingAs($admin)->loadTable(AdminActivityTable::class)->assertOk();
})->with([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT]);

test('a non-admin cannot read the activity trail', function () {
    $this->actingAs(User::factory()->create())->get('/admin/activity')->assertForbidden();
    $this->actingAs(User::factory()->create())->loadDeniedTable(AdminActivityTable::class)->assertForbidden();
});

test('the activity table shows admin rows and hides realm rows', function () {
    $admin = globalAdmin();
    $realm = Realm::factory()->create();

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm);
    Audit::record(AdminAuditEvent::GlobalRolesUpdated, User::factory()->create());

    $rows = $this->actingAs($admin)->loadTable(AdminActivityTable::class)->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('event')->all())->toBe(['admin.roles.updated']);
});

test('the row detail renders the recorded properties for an admin', function () {
    $admin = globalAdmin();
    Audit::record(AdminAuditEvent::GlobalRolesUpdated, User::factory()->create(), properties: ['added' => ['Super Admin']]);

    $activity = Activity::global()->sole();

    $this->actingAs($admin)
        ->loadFragment(AdminActivityChangesFragment::class, ['activity' => $activity->id])
        ->assertOk()
        ->assertSee('Super Admin');
});

test('a non-admin cannot read an activity row detail', function () {
    $fragment = Attributes::get(AdminActivityChangesFragment::class, AsFragment::class);
    assumeNotNull($fragment);

    $key = $fragment->key;
    $url = app(FragmentRegistry::class)->endpointFor($key);
    $ref = app(SignsComponentReferences::class)->seal('fragment', $key, ['activity' => 1]);

    $this->actingAs(User::factory()->create())
        ->getJson($url, ['X-Lattice-Ref' => $ref])
        ->assertForbidden();
});

test('a realm-scoped activity row is not readable through the admin fragment', function () {
    $admin = globalAdmin();
    $realm = Realm::factory()->create();

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['secret' => 'realm-only-sentinel']);

    $activity = Activity::query()->sole();

    $this->actingAs($admin)
        ->loadFragment(AdminActivityChangesFragment::class, ['activity' => $activity->id])
        ->assertOk()
        ->assertDontSee('realm-only-sentinel')
        ->assertSee(__('admin.activity.detail.empty'));
});
