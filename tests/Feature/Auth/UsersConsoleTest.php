<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Tables\UsersTable;
use App\Realms\Models\Realm;
use App\Roles\Models\Role;
use App\Shared\Api\ManagementRoles;
use App\Shared\Authorization\Enums\Scope;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;
use function Tests\Helpers\grantGlobalRole;
use function Tests\Helpers\headerTriggers;

beforeEach(function () {
    $this->realm = Realm::factory()->create(['slug' => 'acme']);
});

test('a users-manage admin can open the realm users page and load its table', function (string $roleName) {
    $this->actingAs(globalAdmin($roleName));
    $this->get('/admin/realms/acme/users')->assertOk();
    $this->loadTable(UsersTable::class, context: ['realm' => 'acme'])->assertOk();
})->with([ManagementRoles::SUPER_ADMIN, ManagementRoles::SUPPORT]);

test('the realm users page and table are forbidden without users-manage', function () {
    $this->actingAs(globalAdminWith(Scope::AdminEventsRead));
    $this->get('/admin/realms/acme/users')->assertForbidden();
    $this->loadDeniedTable(UsersTable::class, context: ['realm' => 'acme'])->assertForbidden();
});

test('the users table lists only the selected realm and searches name and email', function () {
    $byName = User::factory()->for($this->realm)->create(['name' => 'Wanda Needle']);
    $byEmail = User::factory()->for($this->realm)->create(['email' => 'needle@haystack.test']);
    User::factory()->for($this->realm)->create(['name' => 'Someone Else', 'email' => 'someone@example.com']);
    User::factory()->create(['name' => 'Needle Elsewhere']);

    $rows = $this->actingAs(globalAdmin())->loadTable(UsersTable::class, ['q' => 'needle'], ['realm' => 'acme'])->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('id')->all())
        ->toEqualCanonicalizing([$byName->id, $byEmail->id]);
});

test('a user detail page opens under its own realm only', function () {
    $target = User::factory()->for($this->realm)->create();
    $other = Realm::factory()->create(['slug' => 'globex']);

    $this->actingAs(globalAdmin());
    $this->get("/admin/realms/acme/users/{$target->id}")->assertOk();
    $this->get("/admin/realms/globex/users/{$target->id}")->assertNotFound();
    $this->actingAs(User::factory()->for($other)->create())->get("/admin/realms/acme/users/{$target->id}")->assertForbidden();
});

test('a user holding a management scope is flagged as admin in the table', function () {
    $admin = globalAdmin(ManagementRoles::SUPER_ADMIN);
    grantGlobalRole($admin, ManagementRoles::SUPPORT);

    $rows = $this->actingAs($admin)->loadTable(UsersTable::class, context: ['realm' => Realm::master()->slug])->assertOk()->json('data');

    $row = collect(is_array($rows) ? $rows : [])->firstWhere('id', $admin->id);

    expect($row)->not->toBeNull()
        ->and(data_get($row, 'administrates'))->toBeTrue();
});

test('the user detail page offers its actions in the header, led by the two an operator reaches for', function () {
    $active = User::factory()->for($this->realm)->create();
    $blocked = User::factory()->for($this->realm)->create(['blocked_at' => now()]);

    $this->actingAs(globalAdmin());

    expect(headerTriggers($this->get("/admin/realms/acme/users/{$active->id}")->assertOk()))
        ->toBe([
            'admin.users.send-password-reset',
            'admin.users.block',
            'admin.users.delete',
        ])
        ->and(headerTriggers($this->get("/admin/realms/acme/users/{$blocked->id}")->assertOk()))
        ->toBe([
            'admin.users.unblock',
            'admin.users.delete',
        ]);
});

test('a user row opens the detail page and carries the same actions as it', function () {
    $target = User::factory()->for($this->realm)->create();

    $row = $this->actingAs(globalAdmin())->loadTable(UsersTable::class, context: ['realm' => 'acme'])->assertOk()->row($target->id);

    expect($row->clickHref())->toBe("/admin/realms/acme/users/{$target->id}")
        ->and($row->actionIds())->toBe([
            'admin.users.send-password-reset',
            'admin.users.block',
            'admin.users.sessions.end-all',
            'admin.users.delete',
        ]);
});

test('a user row menu follows the state of its own user', function () {
    $blocked = User::factory()->for($this->realm)->create(['blocked_at' => now()]);
    $unverified = User::factory()->for($this->realm)->unverified()->create();

    $table = $this->actingAs(globalAdmin())->loadTable(UsersTable::class, context: ['realm' => 'acme'])->assertOk();

    expect($table->row($blocked->id)->actionIds())
        ->toBe(['admin.users.unblock', 'admin.users.sessions.end-all', 'admin.users.delete'])
        ->and($table->row($unverified->id)->actionIds())
        ->toContain('admin.users.resend-verification');
});

test('an admin cannot block or delete themselves from the users table', function () {
    $admin = globalAdmin();

    $row = $this->actingAs($admin)->loadTable(UsersTable::class, context: ['realm' => Realm::master()->slug])->assertOk()->row($admin->id);

    expect($row->actionIds())
        ->not->toContain('admin.users.block')
        ->not->toContain('admin.users.delete');
});

test('the detail page shows the realm roles a user holds, contributed to its slot', function () {
    $user = User::factory()->for($this->realm)->create();
    $user->roles()->attach(Role::factory()->for($this->realm)->create(['name' => 'member']));

    $this->actingAs(globalAdmin())
        ->get("/admin/realms/acme/users/{$user->id}")
        ->assertOk()
        ->assertSee(__('admin.users.detail.realm-roles.heading'))
        ->assertSee('member');
});
