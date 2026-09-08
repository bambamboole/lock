<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\RealmInvitation;
use App\Realms\Ui\Actions\CancelInvitation;
use App\Realms\Ui\Actions\RemoveMember;
use App\Realms\Ui\Actions\UpdateMemberRole;
use App\Realms\Ui\Forms\InviteRealmMemberForm;
use App\Realms\Ui\Forms\UpdateRealmForm;
use App\Shared\Audit\Models\Activity;

use function Tests\Helpers\addMember;
use function Tests\Helpers\realmMember;

it('logs realm.updated', function () {
    [$user, $realm] = realmMember();

    $this->actingAs($user)
        ->submitForm(UpdateRealmForm::class, ['name' => 'Renamed'], ['realm' => $realm->slug]);

    $a = Activity::query()->where('event', 'realm.updated')->latest('id')->firstOrFail();
    expect($a->realm_id)->toBe($realm->id)
        ->and($a->causer_id)->toBe($user->id)
        ->and($a->properties?->toArray())->toHaveKey('name');
});

it('logs invitation.created', function () {
    [$user, $realm] = realmMember();
    $memberRoleId = $realm->memberRole()->id;

    $this->actingAs($user)
        ->submitForm(InviteRealmMemberForm::class, ['email' => 'new@example.com', 'role_id' => (string) $memberRoleId], ['realm' => $realm->slug]);

    expect(Activity::query()->where('event', 'invitation.created')->where('realm_id', $realm->id)->count())->toBe(1);
    $invited = Activity::query()->where('event', 'invitation.created')->where('realm_id', $realm->id)->latest('id')->firstOrFail();
    expect($invited->properties?->toArray())->toHaveKeys(['email', 'role']);
});

it('logs invitation.cancelled', function () {
    [$user, $realm] = realmMember();

    $invitation = RealmInvitation::factory()->create([
        'realm_id' => $realm->id,
        'invited_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->callAction(CancelInvitation::class, [], ['realm' => $realm->slug, 'invitation' => $invitation->code]);

    expect(Activity::query()->where('event', 'invitation.cancelled')->where('realm_id', $realm->id)->count())->toBe(1);
});

it('logs member.role-changed', function () {
    [$user, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);
    $adminRoleId = $realm->roles()->where('preset', RolePreset::Admin)->value('id');

    $this->actingAs($user)
        ->callAction(UpdateMemberRole::class, ['role_ids' => [(string) $adminRoleId]], ['realm' => $realm->slug, 'member' => $member->id]);

    $changed = Activity::query()->where('event', 'member.role-changed')->latest('id')->firstOrFail();
    expect($changed->realm_id)->toBe($realm->id)
        ->and($changed->properties?->toArray())->toHaveKeys(['roles', 'member']);
});

it('logs member.removed', function () {
    [$user, $realm] = realmMember();
    $member = User::factory()->for($realm)->create();
    addMember($realm, $member);

    $this->actingAs($user)
        ->callAction(RemoveMember::class, [], ['realm' => $realm->slug, 'member' => $member->id]);

    expect(Activity::query()->where('event', 'member.removed')->where('realm_id', $realm->id)->count())->toBe(1);
});
