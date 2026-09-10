<?php
declare(strict_types=1);

namespace App\Realms\Models;

use App\Auth\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\RealmRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A role of a realm's end users, emitted as the `roles` claim of every token
 * issued to them. It carries no permissions and is unrelated to the
 * instance-wide App\Admin\Models\Role.
 *
 * @property string $id
 * @property string $realm_id
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Realm $realm
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\RealmRoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmRole query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['realm_id', 'name', 'description'])]
final class RealmRole extends Model
{
    /** @use HasFactory<RealmRoleFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'realm_role_user')->withTimestamps();
    }

    public function belongsToRealm(Realm $realm): bool
    {
        return $this->realm_id === $realm->id;
    }
}
