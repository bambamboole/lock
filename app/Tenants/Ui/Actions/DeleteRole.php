<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Tenants\Enums\TenantAuditEvent;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenants.roles.delete', can: TenantPermission::ManageRoles, on: 'tenant')]
final class DeleteRole extends ActionDefinition
{
    use ActivatesTenantContext;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('tenant.settings.roles.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('tenant.settings.roles.delete.confirm.title'),
                description: __('tenant.settings.roles.delete.confirm.description'),
                confirmLabel: __('tenant.settings.roles.delete.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var Role $role */
        $role = $this->contextModel('role');

        abort_if($role->isOwner(), 403);

        if ($role->inUse()) {
            return ActionResult::failure(__('tenant.settings.roles.in-use'));
        }

        Audit::record(TenantAuditEvent::RoleDeleted, $role, $tenant, [
            'name' => $role->name,
        ]);

        $role->delete();

        return ActionResult::success()
            ->toast(__('tenant.settings.roles.deleted'), Variant::Success)
            ->reloadComponent('tenants.roles');
    }
}
