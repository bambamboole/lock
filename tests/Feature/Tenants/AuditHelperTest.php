<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;

it('scopes activities to a tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'a', 'event' => 'x', 'tenant_id' => $tenantA->id]);
    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'b', 'event' => 'y', 'tenant_id' => $tenantB->id]);

    expect(Activity::forTenant($tenantA)->count())->toBe(1);
});

it('logs a tenant-scoped activity with causer, event, subject and tenant_id', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $this->actingAs($user);

    Audit::record(TenantAuditEvent::TenantUpdated, $tenant, $tenant, ['name' => ['Old', 'New']]);

    $activity = Activity::query()->latest('id')->firstOrFail();
    expect($activity->log_name)->toBe('tenant')
        ->and($activity->event)->toBe('tenant.updated')
        ->and($activity->tenant_id)->toBe($tenant->id)
        ->and($activity->getRawOriginal('description'))->toBe('audit.events.tenant.updated')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->subject_id)->toBe($tenant->id)
        ->and($activity->properties?->toArray())->toBe(['name' => ['Old', 'New']]);
});

it('resolves the stored description key in the viewer locale at read time', function () {
    $tenant = Tenant::factory()->create();

    Audit::record(TenantAuditEvent::TenantUpdated, $tenant, $tenant);

    $activity = Activity::query()->latest('id')->firstOrFail();
    expect($activity->description)->toBe('Tenant updated');

    app()->setLocale('de');
    expect($activity->description)->toBe('Mandant aktualisiert');
});

it('leaves a pre-existing raw description untranslated', function () {
    $tenant = Tenant::factory()->create();

    Activity::query()->forceCreate(['log_name' => 'tenant', 'description' => 'Legacy text', 'event' => 'tenant.updated', 'tenant_id' => $tenant->id]);

    expect(Activity::query()->latest('id')->firstOrFail()->description)->toBe('Legacy text');
});
