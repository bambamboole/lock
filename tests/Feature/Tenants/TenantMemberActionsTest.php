<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Actions\CancelInvitation;
use App\Tenants\Actions\InviteMember;
use App\Tenants\Actions\RemoveMember;
use App\Tenants\Actions\ResendInvitation;
use App\Tenants\Actions\UpdateMember;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Notifications\TenantInvitation as TenantInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

use function Tests\Helpers\addMember;
use function Tests\Helpers\tenantMember;

/*
 * These call the actions directly — no Lattice request, no signed ref — which is
 * the point of extracting them: a controller, command, or job reaches the same
 * behaviour the forms do.
 */

test('inviting a member persists the invitation and mails it', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);

    $invitation = app(InviteMember::class)->handle($tenant, $owner, 'colleague@example.com');

    expect($invitation->tenant_id)->toBe($tenant->id)
        ->and($invitation->role_id)->toBe($tenant->memberRole()->id)
        ->and($invitation->invited_by)->toBe($owner->id)
        ->and($invitation->code)->not->toBeEmpty()
        ->and($invitation->expires_at)->not->toBeNull();

    Notification::assertSentOnDemand(TenantInvitationNotification::class);
});

test('inviting a member honours an explicit assignable role', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);
    $support = Role::factory()->for($tenant)->create(['name' => 'Support']);

    $invitation = app(InviteMember::class)->handle($tenant, $owner, 'colleague@example.com', $support->id);

    expect($invitation->role_id)->toBe($support->id);
});

test('inviting a member refuses the owner role and a foreign tenant role', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);
    $foreign = Role::factory()->for(Tenant::factory()->create())->create(['name' => 'Outsider']);

    expect(fn () => app(InviteMember::class)->handle($tenant, $owner, 'a@example.com', $tenant->ownerRole()->id))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(InviteMember::class)->handle($tenant, $owner, 'b@example.com', $foreign->id))
        ->toThrow(ValidationException::class);
});

test('inviting an existing member is refused', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);

    app(InviteMember::class)->handle($tenant, $owner, $owner->email);
})->throws(ValidationException::class);

test('resending an invitation extends its expiry and mails it again', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);
    $invitation = TenantInvitation::factory()->for($tenant)->create([
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    app(ResendInvitation::class)->handle($tenant, $owner, $invitation);

    expect($invitation->refresh()->isExpired())->toBeFalse();

    Notification::assertSentOnDemand(TenantInvitationNotification::class);
});

test('an accepted invitation cannot be resent', function () {
    Notification::fake();
    [$owner, $tenant] = tenantMember(switch: false);
    $invitation = TenantInvitation::factory()->for($tenant)->create([
        'invited_by' => $owner->id,
        'accepted_at' => now(),
    ]);

    app(ResendInvitation::class)->handle($tenant, $owner, $invitation);
})->throws(ValidationException::class);

test('cancelling an invitation deletes it', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $invitation = TenantInvitation::factory()->for($tenant)->create(['invited_by' => $owner->id]);

    app(CancelInvitation::class)->handle($tenant, $invitation);

    expect(TenantInvitation::query()->whereKey($invitation->id)->exists())->toBeFalse();
});

test('updating a member replaces their tenant roles with exactly the submitted set', function () {
    [, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Member);
    $support = Role::factory()->for($tenant)->create(['name' => 'Support']);

    app(UpdateMember::class)->handle($tenant, $member, [$support->id]);

    expect($member->rolesFor($tenant)->pluck('id')->all())->toBe([$support->id]);
});

test('updating a member refuses a role that is not assignable in this tenant', function () {
    [, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Member);

    app(UpdateMember::class)->handle($tenant, $member, [$tenant->ownerRole()->id]);
})->throws(ValidationException::class);

test('removing a member drops the membership and their tenant roles', function () {
    [, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member, RolePreset::Member);

    app(RemoveMember::class)->handle($tenant, $member);

    expect($member->belongsToTenant($tenant))->toBeFalse()
        ->and($member->rolesFor($tenant))->toBeEmpty();
});
