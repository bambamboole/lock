<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Fragments\ActivityChangesFragment;
use App\Tenants\Ui\Tables\TenantActivityTable;

use function Tests\Helpers\tenantMember;

it('allows an admin to view the audit log', function () {
    [$admin, $tenant] = tenantMember(RolePreset::Admin, switch: false);

    expect($admin->can(TenantPermission::ViewAudit, $tenant))->toBeTrue();
});

it('renders the tenant activity for an owner and isolates tenants', function () {
    [$owner, $tenantA] = tenantMember(RolePreset::Owner, switch: false);
    $tenantB = Tenant::factory()->create();

    $actor = User::factory()->create(['name' => 'Ada Lovelace']);

    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'A change', 'event' => 'tenant.updated', 'tenant_id' => $tenantA->id, 'causer_type' => User::class, 'causer_id' => $actor->id]);
    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'B change', 'event' => 'member.removed', 'tenant_id' => $tenantB->id]);

    $this->actingAs($owner)
        ->loadTable(TenantActivityTable::class, [], ['tenant' => $tenantA->slug])
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('A change')
        ->assertDontSee('B change');
});

it('filters the activity through the event select instead of free text', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'audit.events.tenant.updated', 'event' => 'tenant.updated', 'tenant_id' => $tenant->id]);
    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'audit.events.member.removed', 'event' => 'member.removed', 'tenant_id' => $tenant->id]);

    $response = $this->actingAs($owner)
        ->loadTable(TenantActivityTable::class, ['filter' => 'event:eq:member.removed'], ['tenant' => $tenant->slug])
        ->assertOk();

    expect($response->collect('data')->pluck('event')->all())->toBe(['member.removed']);
});

it('exposes a lazy changes detail only for activities with properties', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    $withProperties = Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'Renamed', 'event' => 'tenant.updated', 'tenant_id' => $tenant->id, 'properties' => ['name' => ['from' => 'Old', 'to' => 'New']]]);
    $withoutProperties = Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'Touched', 'event' => 'tenant.updated', 'tenant_id' => $tenant->id]);

    $rows = $this->actingAs($owner)
        ->loadTable(TenantActivityTable::class, [], ['tenant' => $tenant->slug])
        ->assertOk()
        ->collect('data');

    expect($rows->firstWhere('id', $withProperties->id))->toHaveKey('detail')
        ->and($rows->firstWhere('id', $withoutProperties->id))->not->toHaveKey('detail');
});

it('renders the recorded changes in the activity detail fragment', function () {
    [$owner, $tenant] = tenantMember(RolePreset::Owner, switch: false);

    $activity = Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'Renamed', 'event' => 'tenant.updated', 'tenant_id' => $tenant->id, 'properties' => ['name' => ['from' => 'Old name', 'to' => 'New name'], 'scopes' => ['tenant:update', 'member:view']]]);

    $content = $this->actingAs($owner)
        ->loadFragment(ActivityChangesFragment::class, ['tenant' => $tenant->slug, 'activity' => $activity->id])
        ->assertOk()
        ->content();

    expect($content)
        ->toContain(json_encode('Name: Old name → New name'))
        ->toContain(json_encode('Scopes: tenant:update, member:view'));
});

it('does not leak another tenant\'s activity through the changes fragment', function () {
    [$owner, $tenantA] = tenantMember(RolePreset::Owner, switch: false);
    $tenantB = Tenant::factory()->create();

    $foreign = Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'Foreign', 'event' => 'tenant.updated', 'tenant_id' => $tenantB->id, 'properties' => ['name' => ['from' => 'Hidden old', 'to' => 'Hidden new']]]);

    $this->actingAs($owner)
        ->loadFragment(ActivityChangesFragment::class, ['tenant' => $tenantA->slug, 'activity' => $foreign->id])
        ->assertNotFound();
});

it('refuses the activity table endpoint to a member without viewAudit', function () {
    [$member, $tenant] = tenantMember(RolePreset::Member, switch: false);

    $this->actingAs($member)
        ->loadDeniedTable(TenantActivityTable::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});
