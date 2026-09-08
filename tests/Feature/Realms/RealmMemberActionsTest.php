<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Actions\CancelInvitation;
use App\Realms\Actions\InviteMember;
use App\Realms\Actions\RemoveMember;
use App\Realms\Actions\ResendInvitation;
use App\Realms\Actions\UpdateMember;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Models\Role;
use App\Realms\Notifications\RealmInvitation as RealmInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

use function Tests\Helpers\addMember;
use function Tests\Helpers\realmMember;

/*
 * These call the actions directly — no Lattice request, no signed ref — which is
 * the point of extracting them: a controller, command, or job reaches the same
 * behaviour the forms do.
 */

test('inviting a member persists the invitation and mails it', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();

    $invitation = app(InviteMember::class)->handle($realm, $owner, 'colleague@example.com');

    expect($invitation->realm_id)->toBe($realm->id)
        ->and($invitation->role_id)->toBe($realm->memberRole()->id)
        ->and($invitation->invited_by)->toBe($owner->id)
        ->and($invitation->code)->not->toBeEmpty()
        ->and($invitation->expires_at)->not->toBeNull();

    Notification::assertSentOnDemand(RealmInvitationNotification::class);
});

test('inviting a member honours an explicit assignable role', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();
    $support = Role::factory()->for($realm)->create(['name' => 'Support']);

    $invitation = app(InviteMember::class)->handle($realm, $owner, 'colleague@example.com', $support->id);

    expect($invitation->role_id)->toBe($support->id);
});

test('inviting a member refuses the owner role and a foreign realm role', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();
    $foreign = Role::factory()->for(Realm::factory()->create())->create(['name' => 'Outsider']);

    expect(fn () => app(InviteMember::class)->handle($realm, $owner, 'a@example.com', $realm->ownerRole()->id))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(InviteMember::class)->handle($realm, $owner, 'b@example.com', $foreign->id))
        ->toThrow(ValidationException::class);
});

test('inviting an existing member is refused', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();

    app(InviteMember::class)->handle($realm, $owner, $owner->email);
})->throws(ValidationException::class);

test('resending an invitation extends its expiry and mails it again', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();
    $invitation = RealmInvitation::factory()->for($realm)->create([
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    app(ResendInvitation::class)->handle($realm, $owner, $invitation);

    expect($invitation->refresh()->isExpired())->toBeFalse();

    Notification::assertSentOnDemand(RealmInvitationNotification::class);
});

test('an accepted invitation cannot be resent', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();
    $invitation = RealmInvitation::factory()->for($realm)->create([
        'invited_by' => $owner->id,
        'accepted_at' => now(),
    ]);

    app(ResendInvitation::class)->handle($realm, $owner, $invitation);
})->throws(ValidationException::class);

test('cancelling an invitation deletes it', function () {
    [$owner, $realm] = realmMember();
    $invitation = RealmInvitation::factory()->for($realm)->create(['invited_by' => $owner->id]);

    app(CancelInvitation::class)->handle($realm, $invitation);

    expect(RealmInvitation::query()->whereKey($invitation->id)->exists())->toBeFalse();
});

test('updating a member replaces their realm roles with exactly the submitted set', function () {
    [, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member, RolePreset::Member);
    $support = Role::factory()->for($realm)->create(['name' => 'Support']);

    app(UpdateMember::class)->handle($realm, $member, [$support->id]);

    expect($member->rolesFor($realm)->pluck('id')->all())->toBe([$support->id]);
});

test('updating a member refuses a role that is not assignable in this realm', function () {
    [, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member, RolePreset::Member);

    app(UpdateMember::class)->handle($realm, $member, [$realm->ownerRole()->id]);
})->throws(ValidationException::class);

test('removing a member deletes them along with their realm roles', function () {
    [, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member, RolePreset::Member);

    app(RemoveMember::class)->handle($realm, $member);

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertDatabaseMissing('user_role', ['user_id' => $member->id]);
});
