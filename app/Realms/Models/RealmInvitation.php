<?php
declare(strict_types=1);

namespace App\Realms\Models;

use App\Auth\Models\User;
use App\Realms\Observers\RealmInvitationObserver;
use Carbon\CarbonImmutable;
use Database\Factories\RealmInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $code
 * @property string $realm_id
 * @property string $email
 * @property int|null $role_id
 * @property string $invited_by
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $inviter
 * @property-read Realm $realm
 * @property-read Role|null $role
 *
 * @method static \Database\Factories\RealmInvitationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereRealmId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RealmInvitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['realm_id', 'email', 'role_id', 'invited_by', 'expires_at', 'accepted_at'])]
#[ObservedBy(RealmInvitationObserver::class)]
#[RouteKey('code')]
final class RealmInvitation extends Model
{
    /** @use HasFactory<RealmInvitationFactory> */
    use HasFactory, HasUuids;

    public static function defaultExpiresAt(): CarbonImmutable
    {
        return now()->addDays(3);
    }

    /**
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
