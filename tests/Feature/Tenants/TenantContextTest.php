<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Tables\TenantMembersTable;
use Illuminate\Support\Facades\Context;
use Lattice\Core\Services\ContextResolutions;
use Lattice\Core\Services\ContextResolvers;

use function Tests\Helpers\addMember;
use function Tests\Helpers\tenantMember;

test('web requests share the current tenant in context for queued jobs', function () {
    $user = User::factory()->create();
    Context::flush();

    $this->actingAs($user)->get('/dashboard');

    expect(Context::get('tenant_id'))->toBe($user->currentTenant()->sole()->id);
});

test('the tenant can be resolved back from context', function () {
    $tenant = Tenant::factory()->create();
    Context::flush();

    expect(Tenant::fromContext())->toBeNull();

    $tenant->makeCurrent();

    expect(Tenant::fromContext()?->id)->toBe($tenant->id);
});

test('switching the current tenant updates the context', function () {
    [$user, $tenant] = tenantMember(RolePreset::Member, switch: false);
    Context::flush();

    $user->switchTenant($tenant);

    expect(Context::get('tenant_id'))->toBe($tenant->id);
});

test('bound tenant models map to the tenant page context', function () {
    $tenant = Tenant::factory()->create();

    expect(app(ContextResolvers::class)->keyForModel($tenant))->toBe('tenant');
});

test('lattice context resolvers scope records through their tenant', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);
    $role = $tenant->memberRole();
    $invitation = TenantInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $user->id,
    ]);
    $activity = Activity::query()->forceCreate([
        'log_name' => 'tenant',
        'description' => 'Tenant updated',
        'event' => 'tenant.updated',
        'tenant_id' => $tenant->id,
    ]);
    $context = [
        'tenant' => $tenant->slug,
        'member' => $user->id,
        'invitation' => $invitation->code,
        'role' => $role->id,
        'activity' => $activity->id,
        'user' => $user->id,
    ];

    $this->actingAs($user);
    request()->setUserResolver(fn (): User => $user);
    $resolutions = app(ContextResolutions::class);

    expect(data_get($resolutions->resolve('tenant', $context['tenant'], $context), 'id'))->toBe($tenant->id)
        ->and(data_get($resolutions->resolve('member', $context['member'], $context), 'id'))->toBe($user->id)
        ->and(data_get($resolutions->resolve('invitation', $context['invitation'], $context), 'id'))->toBe($invitation->id)
        ->and(data_get($resolutions->resolve('role', $context['role'], $context), 'id'))->toBe($role->id)
        ->and(data_get($resolutions->resolve('activity', $context['activity'], $context), 'id'))->toBe($activity->id)
        ->and(data_get($resolutions->resolve('user', $context['user'], $context), 'id'))->toBe($user->id);
});

test('a signed endpoint makes its tenant current for the request without switching the user', function () {
    [$user, $tenant] = tenantMember(RolePreset::Owner, switch: false);
    $currentTenantId = $user->current_tenant_id;
    Context::flush();

    $this->actingAs($user)->loadTable(TenantMembersTable::class, context: ['tenant' => $tenant->slug])->assertOk();

    expect(Context::get('tenant_id'))->toBe($tenant->id)
        ->and($user->refresh()->current_tenant_id)->toBe($currentTenantId);
});

test('rendering the tenant switcher does not switch the current tenant', function () {
    [$user, $current] = tenantMember();
    addMember(Tenant::factory()->create(['name' => 'Zulu']), $user, RolePreset::Member);
    Context::flush();

    $this->actingAs($user)->get(route('dashboard', ['current_tenant' => $current->slug]))->assertOk();

    expect($user->refresh()->current_tenant_id)->toBe($current->id)
        ->and(Context::get('tenant_id'))->toBe($current->id);
});
