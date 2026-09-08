<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Notifications\RealmInvitation as RealmInvitationMail;
use App\Realms\Ui\Actions\CancelInvitation;
use App\Realms\Ui\Actions\ResendInvitation;
use App\Realms\Ui\Forms\InviteRealmMemberForm;
use App\Realms\Ui\Tables\RealmInvitationsTable;
use Illuminate\Support\Facades\Notification;
use Lattice\Notifications\LatticeNotification;

use function Tests\Helpers\addMember;
use function Tests\Helpers\realmMember;

test('realm invitations can be created by', function (RolePreset $inviterRole) {
    Notification::fake();

    [$owner, $realm] = realmMember();

    $inviter = $owner;

    if ($inviterRole !== RolePreset::Owner) {
        $inviter = User::factory()->for($realm)->create();
        addMember($realm, $inviter, $inviterRole);
    }

    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($inviter)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    $this->assertDatabaseHas('realm_invitations', [
        'realm_id' => $realm->id,
        'email' => 'invited@example.com',
        'role_id' => $memberRoleId,
        'invited_by' => $inviter->id,
    ]);
})->with([
    'owners' => RolePreset::Owner,
    'admins' => RolePreset::Admin,
]);

test('existing realm members cannot be invited', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create(['email' => 'member@example.com']);
    addMember($realm, $member);
    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'member@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertSessionHasErrors('email');
});

test('duplicate invitations cannot be created', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertSessionHasErrors('email');
});

test('realm invitations can be cancelled by owners', function () {
    [$owner, $realm] = realmMember();

    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->callAction(CancelInvitation::class, [], ['realm' => $realm->slug, 'invitation' => $invitation->code])
        ->assertOk();

    $this->assertDatabaseMissing('realm_invitations', ['id' => $invitation->id]);
});

test('an invitation can be resent from the invitations table', function () {
    Notification::fake();
    [$owner, $realm] = realmMember();

    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->callAction(ResendInvitation::class, [], ['realm' => $realm->slug, 'invitation' => $invitation->code])
        ->assertOk();

    expect($invitation->refresh()->isExpired())->toBeFalse();

    Notification::assertSentOnDemand(RealmInvitationMail::class);
});

test('a member without the invite permission cannot resend an invitation', function () {
    [$owner, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member, RolePreset::Member);

    $expiredAt = now()->subDay();
    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'invited_by' => $owner->id,
        'expires_at' => $expiredAt,
    ]);

    $this->actingAs($member)
        ->callDeniedAction(ResendInvitation::class, [], ['realm' => $realm->slug, 'invitation' => $invitation->code])
        ->assertForbidden();

    expect($invitation->refresh()->isExpired())->toBeTrue();
});

test('realm invitations can be accepted', function () {
    [$owner, $realm] = realmMember();
    $invitedUser = User::factory()->for($realm)->create(['email' => 'invited@example.com']);

    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertRedirect(route('dashboard'));

    expect($invitation->refresh()->accepted_at)->not->toBeNull();
});

test('realm invitations cannot be accepted by uninvited user', function () {
    [$owner, $realm] = realmMember();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);

    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($uninvitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($uninvitedUser->refresh()->belongsToRealm($realm))->toBeFalse();
});

test('expired invitations cannot be accepted', function () {
    [$owner, $realm] = realmMember();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);

    $invitation = RealmInvitation::factory()->expired()->create([
        'realm_id' => $realm->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($invitedUser->refresh()->belongsToRealm($realm))->toBeFalse();
});

test('inviting an existing user sends them a persistent notification', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com']);

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertSentTo(
        $invited,
        LatticeNotification::class,
        function (LatticeNotification $notification, array $channels) use ($invited, $realm) {
            $data = $notification->toArray($invited);

            return $channels === ['database', 'broadcast']
                && $data['title']->key === 'realm:notification.invitation.title'
                && (array) $data['title']->replacements === ['realm' => $realm->name]
                && str_contains((string) $data['href'], '/accept');
        },
    );
});

test('inviting a user who opted out of invitation notifications sends no persistent notification', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com']);
    $invited->settings->set('notifications.realm-invitations', false);

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertNotSentTo($invited, LatticeNotification::class);
});

test('invitation notifications defer translation to render time', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com', 'locale' => 'de']);

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug]);

    Notification::assertSentTo(
        $invited,
        LatticeNotification::class,
        function (LatticeNotification $notification) use ($invited, $owner, $realm) {
            $body = $notification->toArray($invited)['body'];

            return $body->key === 'realm:notification.invitation.body'
                && (array) $body->replacements === ['inviter' => $owner->name, 'realm' => $realm->name];
        },
    );
});

test('inviting an email without an account sends no persistent notification', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertSentTimes(LatticeNotification::class, 0);
});

test('the invitation mail defaults to the app locale', function () {
    Notification::fake();

    $realm = Realm::factory()->create();
    $owner = User::factory()->for($realm)->create(['locale' => null]);
    addMember($realm, $owner, RolePreset::Owner);
    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertSentOnDemand(
        RealmInvitationMail::class,
        fn (RealmInvitationMail $notification) => $notification->locale === config('app.locale'),
    );
});

test('the invitation mail renders in an existing invited users locale', function () {
    Notification::fake();

    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    User::factory()->create(['email' => 'invited@example.com', 'locale' => 'de']);

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertSentOnDemand(
        RealmInvitationMail::class,
        fn (RealmInvitationMail $notification) => $notification->locale === 'de',
    );
});

test('the invitation mail falls back to the inviters locale for a stranger', function () {
    Notification::fake();

    $realm = Realm::factory()->create();
    $owner = User::factory()->for($realm)->create(['locale' => 'de']);
    addMember($realm, $owner, RolePreset::Owner);
    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug])
        ->assertRedirect(route('realm.settings.edit'));

    Notification::assertSentOnDemand(
        RealmInvitationMail::class,
        fn (RealmInvitationMail $notification) => $notification->locale === 'de',
    );
});

test('the invitations table filters by email through the global search', function () {
    [$owner, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $realm->invitations()->create(['email' => 'alice@example.com', 'role_id' => $memberRoleId, 'invited_by' => $owner->id]);
    $realm->invitations()->create(['email' => 'bob@example.com', 'role_id' => $memberRoleId, 'invited_by' => $owner->id]);

    $this->actingAs($owner)
        ->loadTable(RealmInvitationsTable::class, ['q' => 'alice'], ['realm' => $realm->slug])
        ->assertOk()
        ->assertSee('alice@example.com')
        ->assertDontSee('bob@example.com');
});

test('authorization precedes validation: a member inviting an existing member gets 403 not 422', function () {
    [, $realm] = realmMember();
    $existing = User::factory()->for($realm)->create(['email' => 'member@example.com']);
    addMember($realm, $existing);

    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);
    $roleId = $realm->memberRole()->id;

    $this->actingAs($member)
        ->submitDeniedForm(InviteRealmMemberForm::class, ['email' => 'member@example.com', 'role_id' => (string) $roleId], ['realm' => $realm->slug])
        ->assertForbidden();
});
