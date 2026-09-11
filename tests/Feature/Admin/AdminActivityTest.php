<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Admin\Ui\Fragments\AdminActivityChangesFragment;
use App\Admin\Ui\Tables\AdminActivityTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Enums\RealmAuditEvent;
use Illuminate\Support\Facades\Notification;
use Lattice\Core\Attributes\AsFragment;
use Lattice\Core\Contracts\SignsComponentReferences;
use Lattice\Fragments\FragmentRegistry;
use Spatie\Attributes\Attributes;

use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\globalAdmin;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('the actor name stays an em dash when nothing recorded an actor', function () {
    $realm = Realm::factory()->create();

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm);

    expect(Activity::query()->sole()->causer_name)->toBe('—');
});

test('the actor name is the causing user when one is authenticated', function () {
    $admin = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);
    Audit::record(AdminAuditEvent::GlobalRolesUpdated, $target);

    expect(Activity::query()->sole()->causer_name)->toBe($admin->name);
});

test('granting a global role is logged with the role diff', function () {
    $admin = globalAdmin();
    $target = User::factory()->for(Realm::master())->create();
    $superAdminRole = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$superAdminRole->id],
    ], ['user' => $target->id])->assertOk();

    $activity = Activity::global()->sole();

    expect($activity->event)->toBe('admin.roles.updated')
        ->and($activity->subject_id)->toBe($target->id)
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties?->get('added'))->toBe([GlobalRoles::SUPER_ADMIN])
        ->and($activity->properties?->get('removed'))->toBe([]);
});

test('a role update that changes nothing is not logged', function () {
    $admin = globalAdmin();
    $target = User::factory()->for(Realm::master())->create();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $target->id])->assertOk();

    expect(Activity::global()->count())->toBe(0);
});

test('sending a password reset is logged', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(SendUserPasswordReset::class, [], ['user' => $target->id])->assertOk();

    expect(Activity::global()->sole()->event)->toBe('admin.password-reset.sent');
});

test('resending verification is logged', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])->assertOk();

    expect(Activity::global()->sole()->event)->toBe('admin.verification.resent');
});

test('a rejected verification resend is not logged', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])->assertStatus(422);

    expect(Activity::global()->count())->toBe(0);
});

test('the grant command is logged with a system actor', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    $activity = Activity::global()->sole();

    expect($activity->event)->toBe('admin.roles.granted')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->causer_name)->toBe(__('admin.activity.system-actor'))
        ->and($activity->properties?->get('actor'))->toBe('console:admin:grant')
        ->and($activity->properties?->get('added'))->toBe(['Super Admin'])
        ->and($activity->properties?->get('removed'))->toBe([]);
});

test('re-granting an existing admin is not logged twice', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();
    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    expect(Activity::global()->count())->toBe(1);
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

    Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['secret' => 'value']);

    $activity = Activity::query()->sole();

    $this->actingAs($admin)
        ->loadFragment(AdminActivityChangesFragment::class, ['activity' => $activity->id])
        ->assertOk()
        ->assertDontSee('value')
        ->assertSee(__('admin.activity.detail.empty'));
});
