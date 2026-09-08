<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Auth\Contracts\PermissionEnum;
use App\Auth\Support\PermissionRepository;
use App\Shared\Settings\Concerns\HasSettings;
use App\Shared\Settings\Contracts\ProvidesSettings;
use App\Tenants\Models\Membership;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\Concerns\HasAuthenticationFactors;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $current_tenant_id
 * @property string|null $locale
 * @property string|null $timezone
 * @property-read Tenant|null $currentTenant
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Passkey> $passkeys
 * @property-read int|null $passkeys_count
 * @property-read Membership|null $pivot
 * @property-read Collection<int, Tenant> $tenants
 * @property-read int|null $tenants_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'email', 'password', 'current_tenant_id', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, OAuthenticatable, PasskeyUser, ProvidesSettings
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasAuthenticationFactors, HasFactory, HasSettings, HasUuids, Notifiable;

    /**
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_members', 'user_id', 'tenant_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')->select('roles.*')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function globalRoles(): BelongsToMany
    {
        return $this->roles()->whereNull('roles.tenant_id');
    }

    /**
     * @return Collection<int, Role>
     */
    public function rolesFor(Tenant $tenant): Collection
    {
        return $this->roles()->where('roles.tenant_id', $tenant->id)->get();
    }

    public function personalTenant(): ?Tenant
    {
        return $this->tenants()
            ->where('is_personal', true)
            ->first();
    }

    public function switchTenant(Tenant $tenant): bool
    {
        if (! $this->belongsToTenant($tenant)) {
            return false;
        }

        $this->update(['current_tenant_id' => $tenant->id]);
        $this->setRelation('currentTenant', $tenant);

        $tenant->makeCurrent();

        return true;
    }

    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenants.id', $tenant->id)->exists();
    }

    public function isCurrentTenant(Tenant $tenant): bool
    {
        return $this->current_tenant_id === $tenant->id;
    }

    public function ownsTenant(Tenant $tenant): bool
    {
        return $this->rolesFor($tenant)->contains(fn (Role $role) => $role->isOwner());
    }

    public function fallbackTenant(?Tenant $excluding = null): ?Tenant
    {
        return $this->tenants()
            ->when($excluding, fn ($query, Tenant $excluding) => $query->where('tenants.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(tenants.name)')
            ->first();
    }

    /**
     * The owner short-circuit grants every *tenant-scoped* permission, never a global
     * one — owning a tenant is not an admin grant, so a global permission reached
     * through a tenant role is refused outright.
     */
    public function hasTenantPermission(Tenant $tenant, PermissionEnum $permission): bool
    {
        $value = (string) $permission->value;

        if (app(PermissionRepository::class)->isGlobal($value)) {
            return false;
        }

        $roles = $this->rolesFor($tenant)->load('permissions');

        if ($roles->isEmpty()) {
            return false;
        }

        if ($roles->contains(fn (Role $role) => $role->isOwner())) {
            return true;
        }

        $granted = $roles->flatMap(fn (Role $role) => $role->permissionValues());

        if ($granted->contains($value)) {
            return true;
        }

        return str_ends_with($value, ':view') && $granted->contains(Str::beforeLast($value, ':').':manage');
    }

    public function hasGlobalPermission(PermissionEnum $permission): bool
    {
        return $this->globalRoles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissionValues())
            ->contains($permission->value);
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
