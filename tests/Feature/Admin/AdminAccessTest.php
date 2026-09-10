<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;

use function Tests\Helpers\globalAdmin;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('a non-admin cannot reach the admin area', function () {
    $this->actingAs(User::factory()->create())->get('/admin/realms')->assertForbidden();
});

test('an admin can reach the admin area', function () {
    $this->actingAs(globalAdmin())->get('/admin/realms')->assertOk();
});
