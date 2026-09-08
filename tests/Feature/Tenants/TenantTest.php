<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Actions\SwitchTenant;
use App\Tenants\Ui\Forms\CreateTenantForm;
use App\Tenants\Ui\Forms\DeleteTenantForm;
use App\Tenants\Ui\Forms\UpdateTenantForm;

use function Tests\Helpers\addMember;
use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\tenantMember;

test('tenants can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->submitForm(CreateTenantForm::class, ['name' => 'Test Tenant'])
        ->assertRedirect();

    $this->assertDatabaseHas('tenants', [
        'name' => 'Test Tenant',
        'is_personal' => false,
    ]);
});

test('tenant slug uses next available suffix', function () {
    $user = User::factory()->create();

    Tenant::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Tenant::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Tenant::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this->actingAs($user)
        ->submitForm(CreateTenantForm::class, ['name' => 'Acme']);

    $this->assertDatabaseHas('tenants', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('tenants can be updated by owners', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Original Name']);

    addMember($tenant, $user, RolePreset::Owner);

    $this->actingAs($user)
        ->submitForm(UpdateTenantForm::class, ['name' => 'Updated Name'], ['tenant' => $tenant->slug])
        ->assertRedirect(route('tenant.settings.edit', ['current_tenant' => $tenant->fresh()]));

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'name' => 'Updated Name',
    ]);
});

test('tenants cannot be updated by members', function () {
    [, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($member)
        ->submitDeniedForm(UpdateTenantForm::class, ['name' => 'Updated Name'], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('tenants can be deleted by owners', function () {
    [$user, $tenant] = tenantMember(switch: false);

    $this->actingAs($user)
        ->submitForm(DeleteTenantForm::class, ['name' => $tenant->name], ['tenant' => $tenant->slug])
        ->assertRedirect();

    $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
});

test('tenant deletion requires name confirmation', function () {
    [$user, $tenant] = tenantMember(switch: false);

    $this->actingAs($user)
        ->submitForm(DeleteTenantForm::class, ['name' => 'Wrong Name'], ['tenant' => $tenant->slug])
        ->assertSessionHasErrors('name');

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'deleted_at' => null,
    ]);
});

test('deleting current tenant switches to alphabetically first remaining tenant', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluTenant = Tenant::factory()->create(['name' => 'Zulu Tenant']);
    addMember($zuluTenant, $user, RolePreset::Owner);

    $alphaTenant = Tenant::factory()->create(['name' => 'Alpha Tenant']);
    addMember($alphaTenant, $user, RolePreset::Owner);

    $betaTenant = Tenant::factory()->create(['name' => 'Beta Tenant']);
    addMember($betaTenant, $user, RolePreset::Owner);

    $user->update(['current_tenant_id' => $zuluTenant->id]);

    $this->actingAs($user)
        ->submitForm(DeleteTenantForm::class, ['name' => $zuluTenant->name], ['tenant' => $zuluTenant->slug])
        ->assertRedirect();

    $this->assertSoftDeleted('tenants', ['id' => $zuluTenant->id]);

    expect($user->refresh()->current_tenant_id)->toEqual($alphaTenant->id);
});

test('deleting current tenant falls back to personal tenant when alphabetically first', function () {
    $user = User::factory()->create();
    $personalTenant = $user->personalTenant();
    assumeNotNull($personalTenant);
    $tenant = Tenant::factory()->create(['name' => 'Zulu Tenant']);
    addMember($tenant, $user, RolePreset::Owner);

    $user->update(['current_tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->submitForm(DeleteTenantForm::class, ['name' => $tenant->name], ['tenant' => $tenant->slug])
        ->assertRedirect();

    $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

    expect($user->refresh()->current_tenant_id)->toEqual($personalTenant->id);
});

test('deleting non current tenant leaves current tenant unchanged', function () {
    $user = User::factory()->create();
    $personalTenant = $user->personalTenant();
    assumeNotNull($personalTenant);
    $tenant = Tenant::factory()->create();
    addMember($tenant, $user, RolePreset::Owner);

    $user->update(['current_tenant_id' => $personalTenant->id]);

    $this->actingAs($user)
        ->submitForm(DeleteTenantForm::class, ['name' => $tenant->name], ['tenant' => $tenant->slug])
        ->assertRedirect();

    $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

    expect($user->refresh()->current_tenant_id)->toEqual($personalTenant->id);
});

test('deleting tenant switches other affected users to their personal tenant', function () {
    [$owner, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $owner->update(['current_tenant_id' => $tenant->id]);
    $member->update(['current_tenant_id' => $tenant->id]);

    $memberPersonalTenant = $member->personalTenant();
    assumeNotNull($memberPersonalTenant);

    $this->actingAs($owner)
        ->submitForm(DeleteTenantForm::class, ['name' => $tenant->name], ['tenant' => $tenant->slug])
        ->assertRedirect();

    expect($member->refresh()->current_tenant_id)->toEqual($memberPersonalTenant->id);
});

test('personal tenants cannot be deleted', function () {
    $user = User::factory()->create();
    $personalTenant = $user->personalTenant();
    assumeNotNull($personalTenant);

    $this->actingAs($user)
        ->submitDeniedForm(DeleteTenantForm::class, ['name' => $personalTenant->name], ['tenant' => $personalTenant->slug])
        ->assertForbidden();

    $this->assertDatabaseHas('tenants', [
        'id' => $personalTenant->id,
        'deleted_at' => null,
    ]);
});

test('tenants cannot be deleted by non owners', function () {
    [, $tenant] = tenantMember(switch: false);
    $member = User::factory()->create();
    addMember($tenant, $member);

    $this->actingAs($member)
        ->submitDeniedForm(DeleteTenantForm::class, ['name' => $tenant->name], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('users can switch tenants', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    addMember($tenant, $user, RolePreset::Member);

    $this->actingAs($user)
        ->callAction(SwitchTenant::class, [], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertJsonFragment(['type' => 'redirect', 'url' => route('dashboard', ['current_tenant' => $tenant->slug])]);

    expect($user->refresh()->current_tenant_id)->toEqual($tenant->id);
});

test('users cannot switch to tenant they dont belong to', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->actingAs($user)
        ->callDeniedAction(SwitchTenant::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});
