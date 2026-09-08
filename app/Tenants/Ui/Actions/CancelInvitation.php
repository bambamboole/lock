<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Tenants\Actions\CancelInvitation as CancelInvitationAction;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Models\TenantInvitation;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenants.invitations.cancel', can: TenantPermission::ManageMembers, on: 'tenant')]
final class CancelInvitation extends ActionDefinition
{
    use ActivatesTenantContext;

    public function __construct(private readonly CancelInvitationAction $cancelInvitation) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('tenant.invitations.cancel.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('tenant.invitations.cancel.confirm.title'),
                description: __('tenant.invitations.cancel.confirm.description'),
                confirmLabel: __('tenant.invitations.cancel.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var TenantInvitation $invitation */
        $invitation = $this->contextModel('invitation');

        $this->cancelInvitation->handle($tenant, $invitation);

        return ActionResult::success()
            ->toast(__('tenant.invitations.cancelled'), Variant::Success)
            ->reloadComponent('tenants.invitations');
    }
}
