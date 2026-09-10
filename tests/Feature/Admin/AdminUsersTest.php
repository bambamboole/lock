<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Tables\AdminUsersTable;
use App\Auth\Models\User;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\grantGlobalRole;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('a users-manage admin can load the users table', function (string $roleName) {
    $this->actingAs(globalAdmin($roleName))->loadTable(AdminUsersTable::class)->assertOk();
})->with([GlobalRoleSeeder::SUPER_ADMIN, GlobalRoleSeeder::SUPPORT]);

test('the users table endpoint is forbidden without users-manage', function () {
    $this->actingAs(User::factory()->create())
        ->loadDeniedTable(AdminUsersTable::class)
        ->assertForbidden();
});

test('the users pages are forbidden without users-manage', function () {
    $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
});

test('a users-manage admin can open the users list page', function (string $roleName) {
    $this->actingAs(globalAdmin($roleName))->get('/admin/users')->assertOk();
})->with([GlobalRoleSeeder::SUPER_ADMIN, GlobalRoleSeeder::SUPPORT]);

test('a users-manage admin can open a user detail page', function (string $roleName) {
    $target = User::factory()->create();

    $this->actingAs(globalAdmin($roleName))->get("/admin/users/{$target->id}")->assertOk();
})->with([GlobalRoleSeeder::SUPER_ADMIN, GlobalRoleSeeder::SUPPORT]);

test('the users table searches name and email', function () {
    $admin = globalAdmin(GlobalRoleSeeder::SUPER_ADMIN);
    $byName = User::factory()->create(['name' => 'Wanda Needle']);
    $byEmail = User::factory()->create(['email' => 'needle@haystack.test']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'someone@example.com']);

    $rows = $this->actingAs($admin)->loadTable(AdminUsersTable::class, ['q' => 'needle'])->assertOk()->json('data');

    expect(collect(is_array($rows) ? $rows : [])->pluck('id')->all())
        ->toEqualCanonicalizing([$byName->id, $byEmail->id]);
});

test('the user detail page is forbidden without users-manage', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->create())->get("/admin/users/{$target->id}")->assertForbidden();
});

test('a user holding both global roles is flagged as admin in the table', function () {
    $admin = globalAdmin(GlobalRoleSeeder::SUPER_ADMIN);
    grantGlobalRole($admin, GlobalRoleSeeder::SUPPORT);

    $rows = $this->actingAs($admin)->loadTable(AdminUsersTable::class)->assertOk()->json('data');

    $row = collect(is_array($rows) ? $rows : [])->firstWhere('id', $admin->id);

    expect($row)->not->toBeNull()
        ->and(data_get($row, 'roles_exists'))->toBeTrue();
});
