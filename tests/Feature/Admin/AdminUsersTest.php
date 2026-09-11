<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Admin\Support\GlobalRoles;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;
use function Tests\Helpers\grantGlobalRole;
use function Tests\Helpers\headerTriggers;
use function Tests\Helpers\rowClickHref;
use function Tests\Helpers\rowMenuTriggers;
use function Tests\Helpers\tableRow;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->realm = Realm::factory()->create(['slug' => 'acme']);
});

test('a users-manage admin can open the realm users page and load its table', function (string $roleName) {
    $this->actingAs(globalAdmin($roleName));
    $this->get('/admin/realms/acme/users')->assertOk();
    $this->loadTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertOk();
})->with([GlobalRoles::SUPER_ADMIN, GlobalRoles::SUPPORT]);

test('the realm users page and table are forbidden without users-manage', function () {
    $this->actingAs(globalAdminWith(AdminPermission::Access));
    $this->get('/admin/realms/acme/users')->assertForbidden();
    $this->loadDeniedTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertForbidden();
});

test('the users table lists only the selected realm and searches name and email', function () {
    $byName = User::factory()->for($this->realm)->create(['name' => 'Wanda Needle']);
    $byEmail = User::factory()->for($this->realm)->create(['email' => 'needle@haystack.test']);
    User::factory()->for($this->realm)->create(['name' => 'Someone Else', 'email' => 'someone@example.com']);
    User::factory()->create(['name' => 'Needle Elsewhere']);

    $rows = $this->actingAs(globalAdmin())->loadTable(AdminUsersTable::class, ['q' => 'needle'], ['realm' => 'acme'])->assertOk()->json('data');

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

test('a user holding a global role is flagged as admin in the table', function () {
    $admin = globalAdmin(GlobalRoles::SUPER_ADMIN);
    grantGlobalRole($admin, GlobalRoles::SUPPORT);

    $rows = $this->actingAs($admin)->loadTable(AdminUsersTable::class, context: ['realm' => Realm::master()->slug])->assertOk()->json('data');

    $row = collect(is_array($rows) ? $rows : [])->firstWhere('id', $admin->id);

    expect($row)->not->toBeNull()
        ->and(data_get($row, 'roles_exists'))->toBeTrue();
});

test('the user detail page offers its actions in the header, led by the two an operator reaches for', function () {
    $active = User::factory()->for($this->realm)->create();
    $blocked = User::factory()->for($this->realm)->create(['blocked_at' => now()]);

    $this->actingAs(globalAdmin());

    expect(headerTriggers($this->get("/admin/realms/acme/users/{$active->id}")->assertOk()))
        ->toBe([
            'admin.users.send-password-reset',
            'admin.users.block',
            'admin.users.sessions.end-all',
            'admin.users.delete',
        ])
        ->and(headerTriggers($this->get("/admin/realms/acme/users/{$blocked->id}")->assertOk()))
        ->toBe([
            'admin.users.unblock',
            'admin.users.sessions.end-all',
            'admin.users.delete',
        ]);
});

test('a user row opens the detail page and carries the same actions as it', function () {
    $target = User::factory()->for($this->realm)->create();

    $rows = $this->actingAs(globalAdmin())->loadTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertOk()->json('data');
    $row = tableRow($rows, $target->id);

    expect(rowClickHref($row))->toBe("/admin/realms/acme/users/{$target->id}")
        ->and(rowMenuTriggers($row))->toBe([
            'admin.users.send-password-reset',
            'admin.users.block',
            'admin.users.sessions.end-all',
            'admin.users.delete',
        ]);
});

test('a user row menu follows the state of its own user', function () {
    $blocked = User::factory()->for($this->realm)->create(['blocked_at' => now()]);
    $unverified = User::factory()->for($this->realm)->unverified()->create();

    $rows = $this->actingAs(globalAdmin())->loadTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertOk()->json('data');

    expect(rowMenuTriggers(tableRow($rows, $blocked->id)))
        ->toBe(['admin.users.unblock', 'admin.users.sessions.end-all', 'admin.users.delete'])
        ->and(rowMenuTriggers(tableRow($rows, $unverified->id)))
        ->toContain('admin.users.resend-verification');
});

test('an admin cannot block or delete themselves from the users table', function () {
    $admin = globalAdmin();

    $rows = $this->actingAs($admin)->loadTable(AdminUsersTable::class, context: ['realm' => Realm::master()->slug])->assertOk()->json('data');

    expect(rowMenuTriggers(tableRow($rows, $admin->id)))
        ->not->toContain('admin.users.block')
        ->not->toContain('admin.users.delete');
});
