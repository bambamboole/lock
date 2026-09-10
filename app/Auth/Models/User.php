<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Admin\Models\Role;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Shared\Auth\Contracts\PermissionEnum;
use App\Shared\Settings\Concerns\HasSettings;
use App\Shared\Settings\Contracts\ProvidesSettings;
use Bambamboole\LaravelOidc\Server\Credentials\Concerns\HasAuthenticationFactors;
use Bambamboole\LaravelOidc\Server\Tokens\Concerns\HasAccessTokens;
use Bambamboole\LaravelOidc\Server\Tokens\Contracts\OAuthenticatable;
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
 * @property CarbonImmutable|null $blocked_at
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
 * @property-read Collection<int, RealmRole> $realmRoles
 * @property-read int|null $realm_roles_count
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
    use HasAccessTokens, HasAuthenticationFactors, HasFactory, HasSettings, HasUuids, Notifiable;

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
     * @return BelongsToMany<RealmRole, $this>
     */
    public function realmRoles(): BelongsToMany
    {
        return $this->belongsToMany(RealmRole::class, 'realm_role_user')->withTimestamps();
    }

    /**
     * @return list<string>
     */
    public function realmRoleNames(): array
    {
        return array_values($this->realmRoles()
            ->orderBy('realm_roles.name')
            ->pluck('realm_roles.name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all());
    }

    public function belongsToRealm(Realm $realm): bool
    {
        return $this->realm_id === $realm->id;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * A confirmed authenticator app or a passkey: the factors the login
     * challenges, which is what "MFA active" means to an administrator.
     */
    public function hasSecondFactor(): bool
    {
        return $this->totpFactors()->whereNotNull('confirmed_at')->exists() || $this->passkeys()->exists();
    }

    /**
     * Global permissions come from instance-wide roles, which only users of the
     * master realm can hold: every other realm is data to the admin console.
     */
    public function hasGlobalPermission(PermissionEnum $permission): bool
    {
        if (! $this->realm->isMaster()) {
            return false;
        }

        $value = (string) $permission->value;
        $granted = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissionValues());

        if ($granted->contains($value)) {
            return true;
        }

        return str_ends_with($value, ':view') && $granted->contains(Str::beforeLast($value, ':').':manage');
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
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
