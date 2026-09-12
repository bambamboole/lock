<?php

declare(strict_types=1);
use App\Audit\Models\AdminEvent;
use App\Auth\Models\User;
use App\Auth\Ui\Actions\ResendUserVerification;
use App\Auth\Ui\Actions\SendUserPasswordReset;
use App\Shared\Auth\Enums\UserAdminEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\globalAdminWith;

test('a users-manage admin can resend verification for an unverified user', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])->assertOk();

    Notification::assertSentTo($target, VerifyEmail::class);
    expect(AdminEvent::global()->sole()->type)->toBe(UserAdminEvent::VerificationResent->type());
});

test('resending verification for an already-verified user is rejected', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertStatus(422);

    Notification::assertNothingSent();
    expect(AdminEvent::global()->count())->toBe(0);
});

test('a users-manage admin can send a password reset', function () {
    Notification::fake();
    $admin = globalAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin)->callAction(SendUserPasswordReset::class, [], ['user' => $target->id])->assertOk();

    Notification::assertSentTo($target, ResetPassword::class);
    expect(AdminEvent::global()->sole()->type)->toBe(UserAdminEvent::PasswordResetSent->type());
});

test('an admin without users-manage cannot resend verification', function () {
    Notification::fake();
    $outsider = globalAdminWith(Scope::UsersRead);
    $target = User::factory()->unverified()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(ResendUserVerification::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('an admin without users-manage cannot send a password reset', function () {
    Notification::fake();
    $outsider = globalAdminWith(Scope::UsersRead);
    $target = User::factory()->create();

    $this->actingAs($outsider)
        ->callDeniedAction(SendUserPasswordReset::class, [], ['user' => $target->id])
        ->assertForbidden();

    Notification::assertNothingSent();
});
