<?php

declare(strict_types=1);
use App\Auth\Models\User;
use App\Auth\Ui\Actions\ResendUserVerification;
use App\Auth\Ui\Actions\SendUserPasswordReset;
use App\Realms\Models\Realm;
use App\Roles\Ui\Actions\UpdateUserRoles;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Models\Activity;
use App\Shared\Auth\Enums\UserAuditEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

test('a roles-write admin grants a master realm role, and the console opens for its holder', function () {
    $admin = globalAdmin();
    $target = User::factory()->for(Realm::master())->create();
    $superAdmin = Realm::master()->roles()->where('name', ManagementRoles::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callAction(UpdateUserRoles::class, [
        'role_ids' => [$superAdmin->id],
    ], ['realm' => Realm::master()->slug, 'user' => $target->id])->assertOk();

    expect($target->refresh()->can(ApiScope::RealmsWrite))->toBeTrue();
});

test('clearing a user\'s roles revokes their console access', function () {
    $admin = globalAdmin();
    $target = globalAdmin();

    $this->actingAs($admin)->callAction(UpdateUserRoles::class, [
        'role_ids' => [],
    ], ['realm' => Realm::master()->slug, 'user' => $target->id])->assertOk();

    expect($target->refresh()->managementScopes()->all())->toBeEmpty();
});

test('an admin cannot change their own roles', function () {
    $admin = globalAdmin();

    $this->actingAs($admin)->callDeniedAction(UpdateUserRoles::class, [
        'role_ids' => [],
    ], ['realm' => Realm::master()->slug, 'user' => $admin->id])->assertForbidden();
});

test('support cannot manage roles', function () {
    $support = globalAdmin(ManagementRoles::SUPPORT);
    $target = User::factory()->for(Realm::master())->create();

    $this->actingAs($support)->callDeniedAction(UpdateUserRoles::class, [
        'role_ids' => [],
    ], ['realm' => Realm::master()->slug, 'user' => $target->id])->assertForbidden();
});

test('a users-manage admin can resend verification for an unverified user', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])->assertOk();

    Notification::assertSentTo($target, VerifyEmail::class);
    expect(Activity::global()->sole()->event)->toBe(UserAuditEvent::VerificationResent->eventName());
});

test('resending verification for an already-verified user is rejected', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertStatus(422);

    Notification::assertNothingSent();
    expect(Activity::global()->count())->toBe(0);
});

test('a users-manage admin can send a password reset', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(SendUserPasswordReset::class, [], ['user' => $target->id])->assertOk();

    Notification::assertSentTo($target, ResetPassword::class);
    expect(Activity::global()->sole()->event)->toBe(UserAuditEvent::PasswordResetSent->eventName());
});

test('an admin without users-manage cannot resend verification', function () {
    Notification::fake();
    $outsider = globalAdminWith(ApiScope::UsersRead);
    $target = User::factory()->unverified()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('an admin without users-manage cannot send a password reset', function () {
    Notification::fake();
    $outsider = globalAdminWith(ApiScope::UsersRead);
    $target = User::factory()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(SendUserPasswordReset::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});
