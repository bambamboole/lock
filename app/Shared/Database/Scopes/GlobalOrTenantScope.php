<?php
declare(strict_types=1);

namespace App\Shared\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * For models that carry shared rows (`tenant_id` null) alongside tenant-owned
 * ones. Apply with `#[ScopedBy(GlobalOrTenantScope::class)]` on a model that
 * would otherwise use the strict TenantScope.
 *
 * @implements Scope<Model>
 */
final class GlobalOrTenantScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Context::get('tenant_id');
        $tenantId = is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
        $tenantColumn = $model->qualifyColumn('tenant_id');

        $builder->where(function (Builder $query) use ($tenantId, $tenantColumn): void {
            $query->whereNull($tenantColumn);

            if ($tenantId !== null) {
                $query->orWhere($tenantColumn, $tenantId);
            }
        });
    }
}
