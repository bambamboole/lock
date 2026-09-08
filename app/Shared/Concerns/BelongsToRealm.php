<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Realms\Models\Realm;
use App\Shared\Database\Scopes\RealmScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToRealm
{
    public static function bootBelongsToRealm(): void
    {
        static::addGlobalScope(new RealmScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('realm_id') === null) {
                $model->setAttribute('realm_id', RealmScope::currentRealmId());
            }
        });
    }

    /**
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }
}
