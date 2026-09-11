<?php
declare(strict_types=1);

namespace App\Admin\Models;

use App\Auth\Models\User;
use App\Shared\Auth\Contracts\PermissionCatalog;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 */
#[Fillable(['name'])]
final class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')->withTimestamps();
    }

    /**
     * @return array<int, string>
     */
    public function permissionValues(): array
    {
        return $this->permissions->pluck('key')->all();
    }

    /**
     * The catalog lives in the enums, so the table only holds the keys a role
     * references.
     *
     * @param  array<int, string>  $values
     */
    public function syncPermissions(array $values): void
    {
        $repository = app(PermissionCatalog::class);

        foreach ($values as $value) {
            $repository->get($value);
        }

        $permissionIds = [];

        foreach (array_values(array_unique($values)) as $value) {
            $permissionIds[] = Permission::firstOrCreate(['key' => $value])->id;
        }

        $this->permissions()->sync($permissionIds);
        $this->unsetRelation('permissions');

        Permission::query()->whereNotIn('key', $repository->values())->delete();
    }
}
