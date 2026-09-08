<?php
declare(strict_types=1);

namespace App\Tenants\Models;

use App\Auth\Models\Permission;
use App\Auth\Models\User;
use App\Auth\Support\PermissionRepository;
use App\Tenants\Enums\RolePreset;
use App\Tenants\Enums\TenantPermission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * @property string $id
 * @property int|null $tenant_id
 * @property string $name
 * @property RolePreset|null $preset
 */
#[Fillable(['tenant_id', 'name', 'preset'])]
final class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUuids;

    public static function seedPresetsFor(Tenant $tenant): void
    {
        foreach (RolePreset::cases() as $preset) {
            $role = $tenant->roles()->create([
                'name' => ucfirst($preset->value),
                'preset' => $preset,
            ]);

            $role->syncPermissions(array_map(
                fn (TenantPermission $permission) => $permission->value,
                $preset->defaultPermissions(),
            ));
        }
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')->withTimestamps();
    }

    public function isOwner(): bool
    {
        return $this->preset === RolePreset::Owner;
    }

    public function inUse(): bool
    {
        return $this->users()->exists() || $this->invitations()->whereNull('accepted_at')->exists();
    }

    public function displayName(): string
    {
        if ($this->preset instanceof RolePreset && $this->name === ucfirst($this->preset->value)) {
            return $this->preset->label();
        }

        return $this->name;
    }

    /**
     * @return array<int, string>
     */
    public function permissionValues(): array
    {
        return $this->permissions->pluck('key')->all();
    }

    /**
     * Permission rows are created lazily on first grant — the catalog itself lives in
     * the enums, so the table only ever holds the keys a role actually references.
     *
     * @param  array<int, string>  $values
     */
    public function syncPermissions(array $values): void
    {
        $repository = app(PermissionRepository::class);

        foreach ($values as $value) {
            $permission = $repository->get($value);

            if ($this->tenant_id !== null && $permission->global) {
                throw new InvalidArgumentException("Global permission [{$value}] cannot be assigned to a tenant role.");
            }
        }

        $permissionIds = [];

        foreach (array_values(array_unique($values)) as $value) {
            $permissionIds[] = Permission::firstOrCreate(['key' => $value])->id;
        }

        $this->permissions()->sync($permissionIds);
        $this->unsetRelation('permissions');

        Permission::query()->whereNotIn('key', $repository->values())->delete();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preset' => RolePreset::class,
        ];
    }
}
