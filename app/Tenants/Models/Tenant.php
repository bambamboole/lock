<?php
declare(strict_types=1);

namespace App\Tenants\Models;

use App\Auth\Models\User;
use App\Shared\Settings\Concerns\HasSettings;
use App\Shared\Settings\Contracts\ProvidesSettings;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Observers\TenantObserver;
use Carbon\CarbonImmutable;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\URL;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, TenantInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read Membership|null $pivot
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read Collection<int, Membership> $memberships
 * @property-read int|null $memberships_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static \Database\Factories\TenantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereIsPersonal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug', 'is_personal'])]
#[ObservedBy(TenantObserver::class)]
#[RouteKey('slug')]
final class Tenant extends Model implements ProvidesSettings
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasSettings, HasUuids, SoftDeletes;

    public function owner(): ?User
    {
        $ownerRole = $this->roles()->where('preset', RolePreset::Owner)->first();

        if ($ownerRole === null) {
            return null;
        }

        return $ownerRole->users()->first();
    }

    public static function fromContext(): ?self
    {
        $tenantId = Context::get('tenant_id');

        return is_string($tenantId) || is_int($tenantId) ? self::find($tenantId) : null;
    }

    /**
     * Context is dehydrated into queued jobs, so anything dispatched while this
     * tenant is current can resolve it later via Tenant::fromContext().
     */
    public function makeCurrent(): void
    {
        URL::defaults(['current_tenant' => $this->slug]);
        Context::add('tenant_id', $this->id);
    }

    /**
     * Escape hatch for schedulers, queue workers and maintenance scripts that
     * start without a request-bound tenant. Restores the previous context, so
     * nesting a run inside another tenant's is safe.
     */
    public function runAsCurrent(callable $callback): mixed
    {
        $previousTenantId = Context::get('tenant_id');
        $this->makeCurrent();

        try {
            return $callback();
        } finally {
            if ($previousTenantId === null) {
                Context::forget('tenant_id');
            } else {
                Context::add('tenant_id', $previousTenantId);
            }
        }
    }

    /**
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_members', 'tenant_id', 'user_id')
            ->using(Membership::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function ownerRole(): Role
    {
        return $this->roles()->where('preset', RolePreset::Owner)->firstOrFail();
    }

    public function memberRole(): Role
    {
        return $this->roles()->where('preset', RolePreset::Member)->firstOrFail();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRoles(): Collection
    {
        return $this->roles()->where(fn ($query) => $query->whereNull('preset')->orWhere('preset', '!=', RolePreset::Owner))->orderBy('name')->get();
    }

    public function hasMemberOrPendingInvitation(string $email): bool
    {
        $email = strtolower($email);

        if ($this->members()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return true;
        }

        return $this->invitations()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }
}
