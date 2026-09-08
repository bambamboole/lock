<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Enums\RolePreset;
use App\Realms\Models\Activity;
use App\Realms\Models\Realm;
use App\Realms\Ui\Fragments\ActivityChangesFragment;
use App\Realms\Ui\Tables\RealmActivityTable;

use function Tests\Helpers\realmMember;

it('allows an admin to view the audit log', function () {
    [$admin, $realm] = realmMember(RolePreset::Admin);

    expect($admin->can(RealmPermission::ViewAudit, $realm))->toBeTrue();
});

it('renders the realm activity for an owner and isolates realms', function () {
    [$owner, $realmA] = realmMember(RolePreset::Owner);
    $realmB = Realm::factory()->create();

    $actor = User::factory()->create(['name' => 'Ada Lovelace']);

    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'A change', 'event' => 'realm.updated', 'realm_id' => $realmA->id, 'causer_type' => User::class, 'causer_id' => $actor->id]);
    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'B change', 'event' => 'member.removed', 'realm_id' => $realmB->id]);

    $this->actingAs($owner)
        ->loadTable(RealmActivityTable::class, [], ['realm' => $realmA->slug])
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('A change')
        ->assertDontSee('B change');
});

it('filters the activity through the event select instead of free text', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'audit.events.realm.updated', 'event' => 'realm.updated', 'realm_id' => $realm->id]);
    Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'audit.events.member.removed', 'event' => 'member.removed', 'realm_id' => $realm->id]);

    $response = $this->actingAs($owner)
        ->loadTable(RealmActivityTable::class, ['filter' => 'event:eq:member.removed'], ['realm' => $realm->slug])
        ->assertOk();

    expect($response->collect('data')->pluck('event')->all())->toBe(['member.removed']);
});

it('exposes a lazy changes detail only for activities with properties', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $withProperties = Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'Renamed', 'event' => 'realm.updated', 'realm_id' => $realm->id, 'properties' => ['name' => ['from' => 'Old', 'to' => 'New']]]);
    $withoutProperties = Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'Touched', 'event' => 'realm.updated', 'realm_id' => $realm->id]);

    $rows = $this->actingAs($owner)
        ->loadTable(RealmActivityTable::class, [], ['realm' => $realm->slug])
        ->assertOk()
        ->collect('data');

    expect($rows->firstWhere('id', $withProperties->id))->toHaveKey('detail')
        ->and($rows->firstWhere('id', $withoutProperties->id))->not->toHaveKey('detail');
});

it('renders the recorded changes in the activity detail fragment', function () {
    [$owner, $realm] = realmMember(RolePreset::Owner);

    $activity = Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'Renamed', 'event' => 'realm.updated', 'realm_id' => $realm->id, 'properties' => ['name' => ['from' => 'Old name', 'to' => 'New name'], 'scopes' => ['realm:update', 'member:view']]]);

    $content = $this->actingAs($owner)
        ->loadFragment(ActivityChangesFragment::class, ['realm' => $realm->slug, 'activity' => $activity->id])
        ->assertOk()
        ->content();

    expect($content)
        ->toContain(json_encode('Name: Old name → New name'))
        ->toContain(json_encode('Scopes: realm:update, member:view'));
});

it('does not leak another realm\'s activity through the changes fragment', function () {
    [$owner, $realmA] = realmMember(RolePreset::Owner);
    $realmB = Realm::factory()->create();

    $foreign = Activity::query()->forceCreate(['log_name' => 'realm', 'description' => 'Foreign', 'event' => 'realm.updated', 'realm_id' => $realmB->id, 'properties' => ['name' => ['from' => 'Hidden old', 'to' => 'Hidden new']]]);

    $this->actingAs($owner)
        ->loadFragment(ActivityChangesFragment::class, ['realm' => $realmA->slug, 'activity' => $foreign->id])
        ->assertNotFound();
});

it('refuses the activity table endpoint to a member without viewAudit', function () {
    [$member, $realm] = realmMember(RolePreset::Member);

    $this->actingAs($member)
        ->loadDeniedTable(RealmActivityTable::class, [], ['realm' => $realm->slug])
        ->assertForbidden();
});
