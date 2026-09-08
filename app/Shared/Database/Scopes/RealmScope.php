<?php
declare(strict_types=1);

namespace App\Shared\Database\Scopes;

use App\Shared\Exceptions\MissingRealmContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * Strict by design: a query without a current realm throws instead of silently
 * reading every realm's rows.
 *
 * @implements Scope<Model>
 */
final class RealmScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('realm_id'), self::currentRealmId());
    }

    public static function currentRealmId(): string
    {
        $realmId = Context::get('realm_id');

        if (! is_string($realmId) || $realmId === '') {
            throw new MissingRealmContext;
        }

        return $realmId;
    }
}
