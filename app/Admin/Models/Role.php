<?php
declare(strict_types=1);

namespace App\Admin\Models;

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
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
     * The catalog lives in the enum, so the table only holds the keys a role
     * references.
     *
     * @param  list<AdminPermission>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->sync(array_map(
            fn (AdminPermission $permission): string => Permission::firstOrCreate(['key' => $permission->value])->id,
            $permissions,
        ));
        $this->unsetRelation('permissions');

        Permission::query()->whereNotIn('key', array_column(AdminPermission::cases(), 'value'))->delete();
    }
}
