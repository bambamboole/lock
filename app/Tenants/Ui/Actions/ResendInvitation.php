<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Actions\ResendInvitation as ResendInvitationAction;
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

#[AsAction('tenants.invitations.resend', can: TenantPermission::ManageMembers, on: 'tenant')]
final class ResendInvitation extends ActionDefinition
{
    use ActivatesTenantContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly ResendInvitationAction $resendInvitation) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('tenant.invitations.resend'))
            ->method(HttpMethod::Post)
            ->variant(Variant::Secondary);
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var TenantInvitation $invitation */
        $invitation = $this->contextModel('invitation');

        $this->resendInvitation->handle(
            $tenant,
            $this->currentUser(),
            $invitation,
        );

        return ActionResult::success()
            ->toast(__('tenant.invitations.resent'), Variant::Success)
            ->reloadComponent('tenants.invitations');
    }
}
