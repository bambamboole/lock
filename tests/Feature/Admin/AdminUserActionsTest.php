<?php

declare(strict_types=1);
use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Admin\Models\Role;
use App\Admin\Support\GlobalRoles;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Audit\Models\Activity;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

test('a roles-manage admin can grant a global role to a user, and the grant is recorded', function () {
    $admin = globalAdmin();
    $target = User::factory()->for(Realm::master())->create();
    $superAdminRole = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$superAdminRole->id],
    ], ['user' => $target->id])->assertOk();

    $activity = Activity::global()->sole();

    expect($target->refresh()->can(AdminPermission::Access))->toBeTrue()
        ->and($activity->event)->toBe(AdminAuditEvent::GlobalRolesUpdated->eventName())
        ->and($activity->subject_id)->toBe($target->id)
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties?->get('added'))->toBe([GlobalRoles::SUPER_ADMIN])
        ->and($activity->properties?->get('removed'))->toBe([]);
});

test('global roles cannot be granted to a user outside the master realm', function () {
    $admin = globalAdmin();
    $target = User::factory()->create();
    $superAdminRole = Role::query()->where('name', GlobalRoles::SUPER_ADMIN)->sole();

    $this->actingAs($admin)->callDeniedAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [$superAdminRole->id],
    ], ['user' => $target->id])->assertForbidden();

    expect($target->refresh()->roles()->exists())->toBeFalse();
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
    $support = globalAdmin(GlobalRoles::SUPPORT);
    $target = User::factory()->create();

    $this->actingAs($support)->callDeniedAction(UpdateUserGlobalRoles::class, [
        'role_ids' => [],
    ], ['user' => $target->id])->assertForbidden();
});

test('a users-manage admin can resend verification for an unverified user', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])->assertOk();

    Notification::assertSentTo($target, VerifyEmail::class);
    expect(Activity::global()->sole()->event)->toBe(AdminAuditEvent::VerificationResent->eventName());
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
    expect(Activity::global()->sole()->event)->toBe(AdminAuditEvent::PasswordResetSent->eventName());
});

test('an admin without users-manage cannot resend verification', function () {
    Notification::fake();
    $outsider = globalAdminWith(AdminPermission::Access);
    $target = User::factory()->unverified()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('an admin without users-manage cannot send a password reset', function () {
    Notification::fake();
    $outsider = globalAdminWith(AdminPermission::Access);
    $target = User::factory()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(SendUserPasswordReset::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});
