<?php

declare(strict_types=1);
use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Tests\Helpers\globalAdmin;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('a roles-manage admin can grant a global role to a user', function () {
    $admin = globalAdmin();
    $target = User::factory()->create();
    $superAdminRole = Role::query()->whereNull('realm_id')->where('name', GlobalRoleSeeder::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$superAdminRole->id],
    ], ['user' => $target->id]);

    expect($target->refresh()->can(AdminPermission::Access))->toBeTrue();
});

test('clearing a user\'s global roles revokes their admin access', function () {
    $admin = globalAdmin();
    $target = globalAdmin();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $target->id]);

    expect($target->refresh()->can(AdminPermission::Access))->toBeFalse();
});

test('an admin cannot change their own global roles', function () {
    $admin = globalAdmin();

    $this->actingAs($admin)->callDeniedAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $admin->id])->assertForbidden();
});

test('a support admin cannot manage global roles', function () {
    $support = globalAdmin(GlobalRoleSeeder::SUPPORT);
    $target = User::factory()->create();

    $this->actingAs($support)->callDeniedAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $target->id])->assertForbidden();
});

test('updating global roles does not touch the target user\'s realm roles', function () {
    $admin = globalAdmin();
    $realm = Realm::factory()->create();
    $target = User::factory()->for($realm)->create();
    $realmRole = Role::factory()->for($realm)->create(['name' => 'Support']);
    $target->roles()->attach($realmRole);
    $superAdminRole = Role::query()->whereNull('realm_id')->where('name', GlobalRoleSeeder::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$superAdminRole->id],
    ], ['user' => $target->id]);

    expect($target->refresh()->rolesFor($realm)->pluck('id')->all())->toBe([$realmRole->id]);
});

test('a realm-scoped role id submitted for global roles is not attached', function () {
    $admin = globalAdmin();
    $target = User::factory()->create();
    $realm = Realm::factory()->create();
    $realmRole = Role::factory()->for($realm)->create(['name' => 'Support']);

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$realmRole->id],
    ], ['user' => $target->id]);

    expect($target->refresh()->globalRoles()->pluck('roles.id')->all())->toBeEmpty()
        ->and($target->roles()->pluck('roles.id')->all())->not->toContain($realmRole->id);
});

test('a users-manage admin can resend verification for an unverified user', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id]);

    Notification::assertSentTo($target, VerifyEmail::class);
});

test('resending verification for an already-verified user is rejected', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertStatus(422);

    Notification::assertNothingSent();
});

test('a users-manage admin can send a password reset', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(SendUserPasswordReset::class, [], ['user' => $target->id]);

    Notification::assertSentTo($target, ResetPassword::class);
});

test('a non-admin cannot resend verification', function () {
    Notification::fake();
    $outsider = User::factory()->create();
    $target = User::factory()->unverified()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('a non-admin cannot send a password reset', function () {
    Notification::fake();
    $outsider = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(SendUserPasswordReset::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});
