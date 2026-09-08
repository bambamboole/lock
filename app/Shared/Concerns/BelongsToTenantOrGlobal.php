<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Shared\Database\Scopes\TenantScope;

/**
 * For models scoped with GlobalOrTenantScope (via #[ScopedBy]): disables the
 * tenant-only scope inherited from BusinessModel while keeping creation safe.
 * A new row belongs to the current tenant and creation without a tenant context
 * throws MissingTenantContext — a row shared with every tenant is never
 * implicit, it requires the explicit asGlobal() switch.
 */
trait BelongsToTenantOrGlobal
{
    private bool $createAsGlobal = false;

    public static function bootBelongsToTenant(): void {}

    public static function bootBelongsToTenantOrGlobal(): void
    {
        static::creating(function (self $model): void {
            if ($model->createAsGlobal) {
                $model->setAttribute('tenant_id', null);

                return;
            }

            if ($model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', TenantScope::currentTenantId());
            }
        });
    }

    public function asGlobal(): static
    {
        $this->createAsGlobal = true;

        return $this;
    }
}
