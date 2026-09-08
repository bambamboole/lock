<?php
declare(strict_types=1);

namespace App\Tenants\Models;

use App\Auth\Models\User;
use App\Tenants\Observers\TenantInvitationObserver;
use Carbon\CarbonImmutable;
use Database\Factories\TenantInvitationFactory;
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
 * @property string $tenant_id
 * @property string $email
 * @property int|null $role_id
 * @property string $invited_by
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $inviter
 * @property-read Tenant $tenant
 * @property-read Role|null $role
 *
 * @method static \Database\Factories\TenantInvitationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantInvitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['tenant_id', 'email', 'role_id', 'invited_by', 'expires_at', 'accepted_at'])]
#[ObservedBy(TenantInvitationObserver::class)]
#[RouteKey('code')]
final class TenantInvitation extends Model
{
    /** @use HasFactory<TenantInvitationFactory> */
    use HasFactory, HasUuids;

    public static function defaultExpiresAt(): CarbonImmutable
    {
        return now()->addDays(3);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
