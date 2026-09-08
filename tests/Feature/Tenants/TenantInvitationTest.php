<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Notifications\TenantInvitation as TenantInvitationMail;
use App\Tenants\Ui\Actions\CancelInvitation;
use App\Tenants\Ui\Actions\ResendInvitation;
use App\Tenants\Ui\Forms\InviteTenantMemberForm;
use App\Tenants\Ui\Tables\TenantInvitationsTable;
use Illuminate\Support\Facades\Notification;
use Lattice\Notifications\LatticeNotification;

use function Tests\Helpers\addMember;
use function Tests\Helpers\tenantMember;

test('tenant invitations can be created by', function (RolePreset $inviterRole) {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);

    $inviter = $owner;

    if ($inviterRole !== RolePreset::Owner) {
        $inviter = User::factory()->create();
        addMember($tenant, $inviter, $inviterRole);
    }

    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($inviter)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    $this->assertDatabaseHas('tenant_invitations', [
        'tenant_id' => $tenant->id,
        'email' => 'invited@example.com',
        'role_id' => $memberRoleId,
        'invited_by' => $inviter->id,
    ]);
})->with([
    'owners' => RolePreset::Owner,
    'admins' => RolePreset::Admin,
]);

test('existing tenant members cannot be invited', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create(['email' => 'member@example.com']);
    addMember($tenant, $member);
    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'member@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('email');
});

test('duplicate invitations cannot be created', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('email');
});

test('tenant invitations can be cancelled by owners', function () {
    [$owner, $tenant] = tenantMember(switch: false);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->callAction(CancelInvitation::class, [], ['tenant' => $tenant->slug, 'invitation' => $invitation->code])
        ->assertOk();

    $this->assertDatabaseMissing('tenant_invitations', ['id' => $invitation->id]);
});

test('an invitation can be resent from the invitations table', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->callAction(ResendInvitation::class, [], ['tenant' => $tenant->slug, 'invitation' => $invitation->code])
        ->assertOk();

    expect($invitation->refresh()->isExpired())->toBeFalse();

    Notification::assertSentOnDemand(TenantInvitationMail::class);
});

test('a member without the invite permission cannot resend an invitation', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Member);

    $expiredAt = now()->subDay();
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $owner->id,
        'expires_at' => $expiredAt,
    ]);

    $this->actingAs($member)
        ->callDeniedAction(ResendInvitation::class, [], ['tenant' => $tenant->slug, 'invitation' => $invitation->code])
        ->assertForbidden();

    expect($invitation->refresh()->isExpired())->toBeTrue();
});

test('tenant invitations can be accepted', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertRedirect(route('dashboard', ['current_tenant' => $tenant->slug]));

    expect($invitedUser->refresh()->belongsToTenant($tenant))->toBeTrue()
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();
});

test('tenant invitations cannot be accepted by uninvited user', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($uninvitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($uninvitedUser->refresh()->belongsToTenant($tenant))->toBeFalse();
});

test('expired invitations cannot be accepted', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);

    $invitation = TenantInvitation::factory()->expired()->create([
        'tenant_id' => $tenant->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($invitedUser->refresh()->belongsToTenant($tenant))->toBeFalse();
});

test('inviting an existing user sends them a persistent notification', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com']);

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertSentTo(
        $invited,
        LatticeNotification::class,
        function (LatticeNotification $notification, array $channels) use ($invited, $tenant) {
            $data = $notification->toArray($invited);

            return $channels === ['database', 'broadcast']
                && $data['title']->key === 'tenant:notification.invitation.title'
                && (array) $data['title']->replacements === ['tenant' => $tenant->name]
                && str_contains((string) $data['href'], '/accept');
        },
    );
});

test('inviting a user who opted out of invitation notifications sends no persistent notification', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com']);
    $invited->settings->set('notifications.tenant-invitations', false);

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertNotSentTo($invited, LatticeNotification::class);
});

test('invitation notifications defer translation to render time', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $invited = User::factory()->create(['email' => 'invited@example.com', 'locale' => 'de']);

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug]);

    Notification::assertSentTo(
        $invited,
        LatticeNotification::class,
        function (LatticeNotification $notification) use ($invited, $owner, $tenant) {
            $body = $notification->toArray($invited)['body'];

            return $body->key === 'tenant:notification.invitation.body'
                && (array) $body->replacements === ['inviter' => $owner->name, 'tenant' => $tenant->name];
        },
    );
});

test('inviting an email without an account sends no persistent notification', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertSentTimes(LatticeNotification::class, 0);
});

test('the invitation mail defaults to the app locale', function () {
    Notification::fake();

    $owner = User::factory()->create(['locale' => null]);
    $tenant = Tenant::factory()->create();
    addMember($tenant, $owner, RolePreset::Owner);
    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertSentOnDemand(
        TenantInvitationMail::class,
        fn (TenantInvitationMail $notification) => $notification->locale === config('app.locale'),
    );
});

test('the invitation mail renders in an existing invited users locale', function () {
    Notification::fake();

    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    User::factory()->create(['email' => 'invited@example.com', 'locale' => 'de']);

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'invited@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertSentOnDemand(
        TenantInvitationMail::class,
        fn (TenantInvitationMail $notification) => $notification->locale === 'de',
    );
});

test('the invitation mail falls back to the inviters locale for a stranger', function () {
    Notification::fake();

    $owner = User::factory()->create(['locale' => 'de']);
    $tenant = Tenant::factory()->create();
    addMember($tenant, $owner, RolePreset::Owner);
    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($owner)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'stranger@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant]));

    Notification::assertSentOnDemand(
        TenantInvitationMail::class,
        fn (TenantInvitationMail $notification) => $notification->locale === 'de',
    );
});

test('the invitations table filters by email through the global search', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $tenant->invitations()->create(['email' => 'alice@example.com', 'role_id' => $memberRoleId, 'invited_by' => $owner->id]);
    $tenant->invitations()->create(['email' => 'bob@example.com', 'role_id' => $memberRoleId, 'invited_by' => $owner->id]);

    $this->actingAs($owner)
        ->loadTable(TenantInvitationsTable::class, ['q' => 'alice'], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertSee('alice@example.com')
        ->assertDontSee('bob@example.com');
});

test('authorization precedes validation: a member inviting an existing member gets 403 not 422', function () {
    [, $tenant] = tenantMember(switch: false);
    $existing = User::factory()->create(['email' => 'member@example.com']);
    addMember($tenant, $existing);

    $member = User::factory()->create();
    addMember($tenant, $member);
    $roleId = $tenant->memberRole()->id;

    $this->actingAs($member)
        ->submitDeniedForm(InviteTenantMemberForm::class, ['email' => 'member@example.com', 'role_id' => (string) $roleId], ['tenant' => $tenant->slug])
        ->assertForbidden();
});
