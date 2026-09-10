<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\grantGlobalRole;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
    $this->realm = Realm::factory()->create(['slug' => 'acme']);
});

test('a users-manage admin can open the realm users page and load its table', function (string $roleName) {
    $this->actingAs(globalAdmin($roleName));
    $this->get('/admin/realms/acme/users')->assertOk();
    $this->loadTable(AdminUsersTable::class, context: ['realm' => 'acme'])->assertOk();
})->with([GlobalRoleSeeder::SUPER_ADMIN, GlobalRoleSeeder::SUPPORT]);

test('the realm users page and table are forbidden without users-manage', function () {
    $this->actingAs(User::factory()->create());
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
    $admin = globalAdmin(GlobalRoleSeeder::SUPER_ADMIN);
    grantGlobalRole($admin, GlobalRoleSeeder::SUPPORT);

    $rows = $this->actingAs($admin)->loadTable(AdminUsersTable::class, context: ['realm' => Realm::master()->slug])->assertOk()->json('data');

    $row = collect(is_array($rows) ? $rows : [])->firstWhere('id', $admin->id);

    expect($row)->not->toBeNull()
        ->and(data_get($row, 'roles_exists'))->toBeTrue();
});
