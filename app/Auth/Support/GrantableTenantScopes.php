<?php
declare(strict_types=1);

namespace App\Auth\Support;

use App\Auth\Models\User;
use App\Tenants\Models\Tenant;

/**
 * The API scopes a user may actually grant on a tenant token: the catalog's
 * scopes narrowed to the user's role grants, including the manage-implies-view
 * fallback. The token form and the create action must offer and enforce the
 * same list — a picker that offers a scope the action then refuses validates
 * a control the user never had.
 */
final readonly class GrantableTenantScopes
{
    public function __construct(private PermissionRepository $permissions) {}

    /**
     * @return array<int, string>
     */
    public function for(User $user, Tenant $tenant): array
    {
        return array_values(array_filter(
            array_keys($this->permissions->apiScopes()),
            fn (string $scope): bool => $user->hasTenantPermission($tenant, $this->permissions->get($scope)->case),
        ));
    }
}
