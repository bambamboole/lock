<?php
declare(strict_types=1);

namespace App\Tenants\Actions;

use App\Auth\Models\User;
use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateMember
{
    /**
     * @param  array<int, string>  $roleIds
     */
    public function handle(Tenant $tenant, User $member, array $roleIds): void
    {
        abort_if($member->ownsTenant($tenant), 403, __('tenant.members.owner-role-cannot-be-changed'));

        $roleIds = array_values(array_unique(array_map(strval(...), $roleIds)));
        $roles = $tenant->assignableRoles()->whereIn('id', $roleIds);

        if ($roles->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['role_ids' => [__('validation.exists', ['attribute' => 'role_ids'])]]);
        }

        DB::transaction(function () use ($tenant, $member, $roles): void {
            $member->roles()->detach($tenant->roles()->pluck('id'));
            $member->roles()->attach($roles->pluck('id')->all());
        });

        Audit::record(TenantAuditEvent::MemberRoleChanged, $member, $tenant, [
            'member' => $member->name,
            'roles' => $roles->pluck('name')->all(),
        ]);
    }
}
