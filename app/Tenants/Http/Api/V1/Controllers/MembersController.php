<?php
declare(strict_types=1);

namespace App\Tenants\Http\Api\V1\Controllers;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Http\Api\V1\Resources\MemberResource;
use App\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MembersController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $tenant = $user->currentTenant;
        abort_unless($tenant instanceof Tenant, 403);
        abort_unless($user->can(TenantPermission::ViewMembers, $tenant), 403);

        $members = $tenant->members()->get();
        $members->load(['roles' => fn ($query) => $query->where('roles.tenant_id', $tenant->id)]);

        return MemberResource::collection($members)->response();
    }
}
