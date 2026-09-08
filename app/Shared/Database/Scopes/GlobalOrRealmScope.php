<?php
declare(strict_types=1);

namespace App\Shared\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * For models that carry shared rows (`realm_id` null) alongside realm-owned
 * ones. Apply with `#[ScopedBy(GlobalOrRealmScope::class)]` on a model that
 * would otherwise use the strict RealmScope.
 *
 * @implements Scope<Model>
 */
final class GlobalOrRealmScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $realmId = Context::get('realm_id');
        $realmId = is_string($realmId) && $realmId !== '' ? $realmId : null;
        $realmColumn = $model->qualifyColumn('realm_id');

        $builder->where(function (Builder $query) use ($realmId, $realmColumn): void {
            $query->whereNull($realmColumn);

            if ($realmId !== null) {
                $query->orWhere($realmColumn, $realmId);
            }
        });
    }
}
