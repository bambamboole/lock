<?php
declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Constrains every user lookup to the realm the request belongs to.
 *
 * Identity is realm-local — `users` is unique on (realm_id, email), so the same
 * address can be two unrelated people. Without this constraint a password valid
 * in one realm authenticates in every realm, and a session cookie minted in one
 * resolves a user in another.
 *
 * The constraint only applies once a realm is current. Guests carry no realm
 * yet, so today it binds nothing; it starts protecting the login the moment
 * `SetCurrentRealm` derives a realm from the request instead of the user.
 */
final class RealmUserProvider extends EloquentUserProvider
{
    /**
     * The single seam `retrieveById`, `retrieveByToken` and
     * `retrieveByCredentials` all build their query from.
     *
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null)
    {
        $query = parent::newModelQuery($model);
        $realmId = Realm::currentId();

        return $realmId === null ? $query : $query->where('realm_id', $realmId);
    }
}
