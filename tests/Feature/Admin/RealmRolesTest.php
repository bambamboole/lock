<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Actions\CreateRealmRoleAction;
use App\Admin\Ui\Actions\DeleteRealmRoleAction;
use App\Admin\Ui\Actions\DeleteUserAction;
use App\Admin\Ui\Actions\UpdateRealmRoleAction;
use App\Admin\Ui\Actions\UpdateUserRealmRoles;
use App\Admin\Ui\Forms\DeleteRealmForm;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Auth\Models\User;
use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Shared\Audit\Models\Activity;
use App\Shared\Realms\Contracts\SyncsUserRealmRoles;
use Illuminate\Support\Facades\DB;

use function Tests\Helpers\globalAdmin;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->realm = Realm::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->other = Realm::factory()->create(['name' => 'Globex', 'slug' => 'globex']);
});

test('a users-manage admin creates a realm role and it is recorded', function () {
    $this->actingAs(globalAdmin())->callAction(CreateRealmRoleAction::class, [
        'name' => 'member',
        'description' => '  Standard access  ',
    ], ['realm' => 'acme'])->assertOk();

    $role = RealmRole::query()->sole();

    expect($role->name)->toBe('member')
        ->and($role->description)->toBe('Standard access')
        ->and($role->realm_id)->toBe($this->realm->id);

    $activity = Activity::global()->where('event', RealmAuditEvent::RoleCreated->eventName())->sole();

    expect($activity->subject_id)->toBe($role->id)
        ->and($activity->getProperty('realm'))->toBe('acme')
        ->and($activity->getProperty('name'))->toBe('member');
});

test('a realm role name is unique per realm but free in another', function () {
    RealmRole::factory()->for($this->realm)->create(['name' => 'member']);

    $this->actingAs(globalAdmin());
    $this->callAction(CreateRealmRoleAction::class, ['name' => 'member'], ['realm' => 'acme'])->assertInvalid(['name']);
    $this->callAction(CreateRealmRoleAction::class, ['name' => 'member'], ['realm' => 'globex'])->assertOk();

    expect(RealmRole::query()->where('name', 'member')->count())->toBe(2);
});

test('a realm role name is rejected when it is not token safe', function (string $name) {
    $this->actingAs(globalAdmin())
        ->callAction(CreateRealmRoleAction::class, ['name' => $name], ['realm' => 'acme'])
        ->assertInvalid(['name']);

    expect(RealmRole::query()->exists())->toBeFalse();
})->with([
    'whitespace' => 'realm admin',
    'leading punctuation' => '-admin',
    'quotes' => 'ad"min',
]);

test('renaming a realm role records the change and follows through to the claim', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member', 'description' => null]);
    $user = User::factory()->for($this->realm)->create();
    $user->realmRoles()->attach($role);

    $this->actingAs(globalAdmin())->callAction(UpdateRealmRoleAction::class, [
        'name' => 'staff',
        'description' => 'Renamed',
    ], ['realm' => 'acme', 'realmRole' => $role->id])->assertOk();

    expect($user->refresh()->realmRoleNames())->toBe(['staff']);

    $activity = Activity::global()->where('event', RealmAuditEvent::RoleUpdated->eventName())->sole();

    expect($activity->getProperty('changes'))->toBe([
        'name' => ['old' => 'member', 'new' => 'staff'],
        'description' => ['old' => null, 'new' => 'Renamed'],
    ]);
});

test('deleting a realm role detaches every user that holds it', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $kept = RealmRole::factory()->for($this->realm)->create(['name' => 'admin']);
    $user = User::factory()->for($this->realm)->create();
    $user->realmRoles()->attach([$role->id, $kept->id]);

    $this->actingAs(globalAdmin())
        ->callAction(DeleteRealmRoleAction::class, [], ['realm' => 'acme', 'realmRole' => $role->id])
        ->assertOk();

    expect(RealmRole::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and($user->refresh()->realmRoleNames())->toBe(['admin']);

    $activity = Activity::global()->where('event', RealmAuditEvent::RoleDeleted->eventName())->sole();

    expect($activity->getProperty('name'))->toBe('member')
        ->and($activity->getProperty('users-detached'))->toBe(1);
});

test('the roles table lists only the roles of the selected realm with their user counts', function () {
    $member = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    RealmRole::factory()->for($this->realm)->create(['name' => 'admin']);
    RealmRole::factory()->for($this->other)->create(['name' => 'elsewhere']);
    User::factory()->for($this->realm)->create()->realmRoles()->attach($member);

    $rows = $this->actingAs(globalAdmin())
        ->loadTable(AdminRealmRolesTable::class, context: ['realm' => 'acme'])
        ->assertOk()
        ->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('name')->all())->toBe(['admin', 'member'])
        ->and(collect(is_array($rows) ? $rows : [])->firstWhere('name', 'member')['users_count'])->toBe(1);
});

test('a realm role is only reachable through its own realm', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $foreign = ['realm' => 'globex', 'realmRole' => $role->id];

    $this->actingAs(globalAdmin());
    $this->callDeniedAction(UpdateRealmRoleAction::class, ['name' => 'renamed'], $foreign)->assertForbidden();
    $this->callDeniedAction(DeleteRealmRoleAction::class, [], $foreign)->assertForbidden();

    expect($role->refresh()->name)->toBe('member');
});

test('the roles console is forbidden without users-manage', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $context = ['realm' => 'acme', 'realmRole' => $role->id];

    $this->actingAs(User::factory()->for($this->realm)->create());
    $this->get('/admin/realms/acme/roles')->assertForbidden();
    $this->loadDeniedTable(AdminRealmRolesTable::class, context: ['realm' => 'acme'])->assertForbidden();
    $this->callDeniedAction(CreateRealmRoleAction::class, ['name' => 'other'], ['realm' => 'acme'])->assertForbidden();
    $this->callDeniedAction(UpdateRealmRoleAction::class, ['name' => 'other'], $context)->assertForbidden();
    $this->callDeniedAction(DeleteRealmRoleAction::class, [], $context)->assertForbidden();

    expect(RealmRole::query()->count())->toBe(1);
});

test('assigning realm roles to a user replaces the set and records what changed', function () {
    $member = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $admin = RealmRole::factory()->for($this->realm)->create(['name' => 'admin']);
    $user = User::factory()->for($this->realm)->create();
    $user->realmRoles()->attach($member);

    $this->actingAs(globalAdmin())->callAction(UpdateUserRealmRoles::class, [
        'role_ids' => [$admin->id],
    ], ['realm' => 'acme', 'user' => $user->id])->assertOk();

    expect($user->refresh()->realmRoleNames())->toBe(['admin']);

    $activity = Activity::global()->where('event', RealmAuditEvent::UserRolesUpdated->eventName())->sole();

    expect($activity->subject_id)->toBe($user->id)
        ->and($activity->getProperty('added'))->toBe(['admin'])
        ->and($activity->getProperty('removed'))->toBe(['member']);
});

test('a role of another realm cannot be assigned to a user', function () {
    $foreign = RealmRole::factory()->for($this->other)->create(['name' => 'elsewhere']);
    $user = User::factory()->for($this->realm)->create();

    $this->actingAs(globalAdmin())->callAction(UpdateUserRealmRoles::class, [
        'role_ids' => [$foreign->id],
    ], ['realm' => 'acme', 'user' => $user->id])->assertInvalid(['role_ids']);

    expect($user->refresh()->realmRoles()->exists())->toBeFalse();
});

test('the role sync action itself refuses a role outside the user\'s realm', function () {
    $foreign = RealmRole::factory()->for($this->other)->create(['name' => 'elsewhere']);
    $user = User::factory()->for($this->realm)->create();

    expect(fn () => app(SyncsUserRealmRoles::class)->handle($user, [$foreign->id]))
        ->toThrow(InvalidArgumentException::class)
        ->and($user->realmRoles()->exists())->toBeFalse();
});

test('a user detail page opens the realm roles card for every realm', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $user = User::factory()->for($this->realm)->create();
    $user->realmRoles()->attach($role);

    $this->actingAs(globalAdmin())->get("/admin/realms/acme/users/{$user->id}")->assertOk()
        ->assertSee(__('admin.users.detail.realm-roles.heading'));
});

test('deleting a user releases their realm role assignments', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $user = User::factory()->for($this->realm)->create();
    $user->realmRoles()->attach($role);

    $this->actingAs(globalAdmin())
        ->callAction(DeleteUserAction::class, [], ['realm' => 'acme', 'user' => $user->id])
        ->assertOk();

    expect(DB::table('realm_role_user')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(RealmRole::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('deleting a realm takes its roles and their assignments with it', function () {
    $role = RealmRole::factory()->for($this->realm)->create(['name' => 'member']);
    $kept = RealmRole::factory()->for($this->other)->create(['name' => 'elsewhere']);
    User::factory()->for($this->realm)->create()->realmRoles()->attach($role);

    $this->actingAs(globalAdmin())
        ->submitForm(DeleteRealmForm::class, ['name' => 'Acme'], ['realm' => 'acme'])
        ->assertRedirect(route('admin.realms'));

    expect(RealmRole::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and(DB::table('realm_role_user')->where('realm_role_id', $role->id)->exists())->toBeFalse()
        ->and(RealmRole::query()->whereKey($kept->id)->exists())->toBeTrue();
});
