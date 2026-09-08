<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use App\Shared\Database\Scopes\RealmScope;

/**
 * For models scoped with GlobalOrRealmScope (via #[ScopedBy]): disables the
 * realm-only scope inherited from BusinessModel while keeping creation safe.
 * A new row belongs to the current realm and creation without a realm context
 * throws MissingRealmContext — a row shared with every realm is never
 * implicit, it requires the explicit asGlobal() switch.
 */
trait BelongsToRealmOrGlobal
{
    private bool $createAsGlobal = false;

    public static function bootBelongsToRealm(): void {}

    public static function bootBelongsToRealmOrGlobal(): void
    {
        static::creating(function (self $model): void {
            if ($model->createAsGlobal) {
                $model->setAttribute('realm_id', null);

                return;
            }

            if ($model->getAttribute('realm_id') === null) {
                $model->setAttribute('realm_id', RealmScope::currentRealmId());
            }
        });
    }

    public function asGlobal(): static
    {
        $this->createAsGlobal = true;

        return $this;
    }
}
