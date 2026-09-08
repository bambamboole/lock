<?php
declare(strict_types=1);

use App\Shared\Database\Scopes\TenantScope;
use App\Shared\Exceptions\MissingTenantContext;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\ScopedRecord;
use Tests\Fixtures\SharedRecord;

use function Tests\Helpers\assumeNotNull;

beforeEach(function () {
    ScopedRecord::createTable();
    SharedRecord::createTable();
});

it('stamps the current tenant on create without the model asking for it', function () {
    $tenant = Tenant::factory()->create();

    $record = $tenant->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'Widget']));

    expect($record->tenant_id)->toBe($tenant->id)
        ->and($record->tenant->is($tenant))->toBeTrue();
});

it('hides another tenant rows from every query', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $tenantA->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'A']));
    $tenantB->runAsCurrent(fn () => ScopedRecord::query()->create(['name' => 'B']));

    expect($tenantA->runAsCurrent(fn () => ScopedRecord::query()->pluck('name')->all()))->toBe(['A'])
        ->and($tenantB->runAsCurrent(fn () => ScopedRecord::query()->pluck('name')->all()))->toBe(['B'])
        ->and(ScopedRecord::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(2);
});

it('refuses to query or create without a current tenant', function () {
    Context::forget('tenant_id');

    expect(fn () => ScopedRecord::query()->count())->toThrow(MissingTenantContext::class)
        ->and(fn () => ScopedRecord::query()->create(['name' => 'Orphan']))->toThrow(MissingTenantContext::class);
});

it('restores the previous tenant context after a nested run', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Context::forget('tenant_id');

    $inner = $tenantA->runAsCurrent(function () use ($tenantA, $tenantB): array {
        $nested = $tenantB->runAsCurrent(fn () => Context::get('tenant_id'));

        expect(Context::get('tenant_id'))->toBe($tenantA->id);

        return [$nested];
    });

    expect($inner)->toBe([$tenantB->id])
        ->and(Context::get('tenant_id'))->toBeNull();
});

it('shows shared rows alongside the current tenant under GlobalOrTenantScope', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $tenantA->runAsCurrent(fn () => SharedRecord::query()->create(['name' => 'A']));
    $tenantB->runAsCurrent(fn () => SharedRecord::query()->create(['name' => 'B']));
    new SharedRecord(['name' => 'Shared'])->asGlobal()->save();

    expect($tenantA->runAsCurrent(fn () => SharedRecord::query()->pluck('name')->all()))
        ->toEqualCanonicalizing(['A', 'Shared'])
        ->and(SharedRecord::query()->pluck('name')->all())->toBe(['Shared']);
});

it('refuses to create a shared-scoped row without a tenant unless it is marked global', function () {
    Context::forget('tenant_id');

    expect(fn () => SharedRecord::query()->create(['name' => 'Implicit']))->toThrow(MissingTenantContext::class);
});

it('answers an api request without a tenant context with a 403 instead of a 500', function () {
    Context::forget('tenant_id');

    Route::middleware('api')->get('/api/scoping-probe', fn () => ScopedRecord::query()->count());

    $this->getJson('/api/scoping-probe')
        ->assertForbidden()
        ->assertJson(['message' => __('tenant.api.no-tenant-context')]);
});

it('stamps the tenant on the audit trail of a scoped record', function () {
    $tenant = Tenant::factory()->create();

    $tenant->runAsCurrent(function () {
        $record = ScopedRecord::query()->create(['name' => 'Widget']);
        $record->update(['name' => 'Gadget']);
    });

    $activity = Activity::query()->where('subject_type', ScopedRecord::class)->where('event', 'updated')->sole();
    $changes = $activity->attribute_changes;
    assumeNotNull($changes);

    expect($activity->tenant_id)->toBe($tenant->id)
        ->and($activity->log_name)->toBe('tenant')
        ->and($changes->get('attributes'))->toBe(['name' => 'Gadget']);
});
