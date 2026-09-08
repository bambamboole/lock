<?php

declare(strict_types=1);
use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('admin:grant makes a user an admin', function () {
    $user = User::factory()->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();
    expect($user->refresh()->can(AdminPermission::Access))->toBeTrue();
});

test('admin:grant fails for an unknown email', function () {
    $this->artisan('admin:grant', ['email' => 'nobody@example.com'])->assertFailed();
});
