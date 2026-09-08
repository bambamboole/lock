<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Auth\Models\User;
use App\Tenants\Actions\RemoveMember as RemoveMemberAction;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenants.members.remove', can: TenantPermission::ManageMembers, on: 'tenant')]
final class RemoveMember extends ActionDefinition
{
    use ActivatesTenantContext;

    public function __construct(private readonly RemoveMemberAction $removeMember) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('tenant.members.remove.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('tenant.members.remove.confirm.title'),
                description: __('tenant.members.remove.confirm.description'),
                confirmLabel: __('tenant.members.remove.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var User $member */
        $member = $this->contextModel('member');

        $this->removeMember->handle($tenant, $member);

        return ActionResult::success()
            ->toast(__('tenant.members.removed'), Variant::Success)
            ->reloadComponent('tenants.members');
    }
}
