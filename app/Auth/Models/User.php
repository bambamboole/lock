<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Auth\Support\PermissionRepository;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Shared\Auth\Contracts\PermissionEnum;
use App\Shared\Settings\Concerns\HasSettings;
use App\Shared\Settings\Contracts\ProvidesSettings;
use Bambamboole\LaravelOidc\Server\Credentials\Concerns\HasAuthenticationFactors;
use Bambamboole\LaravelOidc\Server\Users\Concerns\HasOidcTokens;
use Bambamboole\LaravelOidc\Server\Users\OAuthenticatable;
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

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string $realm_id
 * @property string|null $locale
 * @property string|null $timezone
 * @property-read Realm $realm
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Passkey> $passkeys
 * @property-read int|null $passkeys_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRealmId($value)
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
#[Fillable(['name', 'email', 'password', 'realm_id', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, OAuthenticatable, PasskeyUser, ProvidesSettings
{
    /** @use HasFactory<UserFactory> */
    use HasAuthenticationFactors, HasFactory, HasOidcTokens, HasSettings, HasUuids, Notifiable;

    /**
     * The one realm this user exists in. Identity does not cross realms: two
     * rows with the same email in different realms are two unrelated people.
     *
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
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
        return $this->roles()->whereNull('roles.realm_id');
    }

    /**
     * Roles this user holds in the given realm. A realm other than the user's
     * own yields nothing — the caller passes a realm so the guard is explicit
     * at the call site rather than assumed.
     *
     * @return Collection<int, Role>
     */
    public function rolesFor(Realm $realm): Collection
    {
        if (! $this->belongsToRealm($realm)) {
            return new Collection;
        }

        return $this->roles()->where('roles.realm_id', $realm->id)->get();
    }

    public function belongsToRealm(Realm $realm): bool
    {
        return $this->realm_id === $realm->id;
    }

    public function ownsRealm(Realm $realm): bool
    {
        return $this->rolesFor($realm)->contains(fn (Role $role) => $role->isOwner());
    }

    /**
     * The owner short-circuit grants every *realm-scoped* permission, never a global
     * one — owning a realm is not an admin grant, so a global permission reached
     * through a realm role is refused outright.
     */
    public function hasRealmPermission(Realm $realm, PermissionEnum $permission): bool
    {
        $value = (string) $permission->value;

        if (app(PermissionRepository::class)->isGlobal($value)) {
            return false;
        }

        $roles = $this->rolesFor($realm)->load('permissions');

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
