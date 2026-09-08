<?php
declare(strict_types=1);

namespace App\Shared\Database\Scopes;

use App\Shared\Exceptions\MissingTenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * Strict by design: a query without a current tenant throws instead of silently
 * reading every tenant's rows.
 *
 * @implements Scope<Model>
 */
final class TenantScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('tenant_id'), self::currentTenantId());
    }

    public static function currentTenantId(): string
    {
        $tenantId = Context::get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            throw new MissingTenantContext;
        }

        return $tenantId;
    }
}
