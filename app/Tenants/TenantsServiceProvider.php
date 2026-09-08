<?php

declare(strict_types=1);

namespace App\Tenants;

use App\Auth\Models\User;
use App\Tenants\Models\Activity;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Settings\ActivityTab;
use App\Tenants\Ui\Settings\DangerTab;
use App\Tenants\Ui\Settings\GeneralTab;
use App\Tenants\Ui\Settings\MembersTab;
use App\Tenants\Ui\Settings\RolesTab;
use Illuminate\Support\ServiceProvider;
use Lattice\Core\Facades\Lattice;
use Lattice\Core\Services\ContextResolutions;

final class TenantsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Lattice::context(
            'tenant',
            fn (string $value): Tenant => Tenant::where('slug', $value)->firstOrFail(),
            keyBy: fn (Tenant $tenant): string => $tenant->slug,
        );

        Lattice::context('member', function (string $value, array $context, ContextResolutions $resolutions): User {
            $tenant = $resolutions->resolve('tenant', $context['tenant'] ?? null, $context);
            abort_unless($tenant instanceof Tenant, 404);

            return $tenant->members()->findOrFail($value);
        }, keyBy: fn (User $member): string => $member->id);

        Lattice::context('invitation', function (string $value, array $context, ContextResolutions $resolutions): TenantInvitation {
            $tenant = $resolutions->resolve('tenant', $context['tenant'] ?? null, $context);
            abort_unless($tenant instanceof Tenant, 404);

            return $tenant->invitations()->where('code', $value)->firstOrFail();
        }, keyBy: fn (TenantInvitation $invitation): string => $invitation->code);

        Lattice::context('role', function (string $value, array $context, ContextResolutions $resolutions): Role {
            $tenant = $resolutions->resolve('tenant', $context['tenant'] ?? null, $context);
            abort_unless($tenant instanceof Tenant, 404);

            return $tenant->roles()->findOrFail($value);
        }, keyBy: fn (Role $role): string => $role->id);

        Lattice::context('activity', function (string|int $value, array $context, ContextResolutions $resolutions): Activity {
            $tenant = $resolutions->resolve('tenant', $context['tenant'] ?? null, $context);
            abort_unless($tenant instanceof Tenant, 404);

            return Activity::query()->forTenant($tenant)->findOrFail($value);
        }, keyBy: fn (Activity $activity): int => (int) $activity->getKey());

        Lattice::extend('tenant.settings.tabs', (new GeneralTab)(...), priority: 10);
        Lattice::extend('tenant.settings.tabs', (new MembersTab)(...), priority: 20);
        Lattice::extend('tenant.settings.tabs', (new RolesTab)(...), priority: 25);
        Lattice::extend('tenant.settings.tabs', (new ActivityTab)(...), priority: 40);
        Lattice::extend('tenant.settings.tabs', (new DangerTab)(...), priority: 100);
    }
}
