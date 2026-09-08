<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Activity;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Actions\CancelInvitation;
use App\Tenants\Ui\Actions\RemoveMember;
use App\Tenants\Ui\Actions\UpdateMemberRole;
use App\Tenants\Ui\Forms\InviteTenantMemberForm;
use App\Tenants\Ui\Forms\UpdateTenantForm;

use function Tests\Helpers\addMember;
use function Tests\Helpers\tenantMember;

it('logs tenant.updated', function () {
    [$user, $tenant] = tenantMember(switch: false);

    $this->actingAs($user)
        ->submitForm(UpdateTenantForm::class, ['name' => 'Renamed'], ['tenant' => $tenant->slug]);

    $a = Activity::query()->where('event', 'tenant.updated')->latest('id')->firstOrFail();
    expect($a->tenant_id)->toBe($tenant->id)
        ->and($a->causer_id)->toBe($user->id)
        ->and($a->properties?->toArray())->toHaveKey('name');
});

it('logs invitation.created', function () {
    [$user, $tenant] = tenantMember(switch: false);
    $memberRoleId = $tenant->memberRole()->id;

    $this->actingAs($user)
        ->submitForm(InviteTenantMemberForm::class, ['email' => 'new@example.com', 'role_id' => (string) $memberRoleId], ['tenant' => $tenant->slug]);

    expect(Activity::query()->where('event', 'invitation.created')->where('tenant_id', $tenant->id)->count())->toBe(1);
    $invited = Activity::query()->where('event', 'invitation.created')->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
    expect($invited->properties?->toArray())->toHaveKeys(['email', 'role']);
});

it('logs invitation.cancelled', function () {
    [$user, $tenant] = tenantMember(switch: false);

    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->callAction(CancelInvitation::class, [], ['tenant' => $tenant->slug, 'invitation' => $invitation->code]);

    expect(Activity::query()->where('event', 'invitation.cancelled')->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('logs member.role-changed', function () {
    [$user, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);
    $adminRoleId = $tenant->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($user)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['tenant' => $tenant->slug, 'member' => $member->id]);

    $changed = Activity::query()->where('event', 'member.role-changed')->latest('id')->firstOrFail();
    expect($changed->tenant_id)->toBe($tenant->id)
        ->and($changed->properties?->toArray())->toHaveKeys(['roles', 'member']);
});

it('logs member.removed', function () {
    [$user, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($user)
        ->callAction(RemoveMember::class, [], ['tenant' => $tenant->slug, 'member' => $member->id]);

    expect(Activity::query()->where('event', 'member.removed')->where('tenant_id', $tenant->id)->count())->toBe(1);
});
