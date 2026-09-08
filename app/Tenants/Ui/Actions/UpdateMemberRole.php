<?php
declare(strict_types=1);

namespace App\Tenants\Ui\Actions;

use App\Auth\Models\User;
use App\Tenants\Actions\UpdateMember;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenants.members.update', can: TenantPermission::ManageMembers, on: 'tenant')]
final class UpdateMemberRole extends ActionDefinition
{
    use ActivatesTenantContext;

    public function __construct(private readonly UpdateMember $updateMember) {}

    public function definition(Action $action): Action
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');

        return $action
            ->label(__('tenant.members.change-role'))
            ->method(HttpMethod::Patch)
            ->variant(Variant::Secondary)
            ->form([
                CheckboxGroup::make('role_ids', __('common.field.role'))
                    ->options(
                        $tenant->assignableRoles()
                            ->map(fn (Role $role) => CheckboxGroup::option($role->displayName(), (string) $role->id))
                            ->all(),
                    )
                    ->rules(['min:1'])
                    ->required(),
            ]);
    }

    public function handle(FormData $data): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        /** @var User $member */
        $member = $this->contextModel('member');

        $this->updateMember->handle(
            $tenant,
            $member,
            array_values((array) $data['role_ids']),
        );

        return ActionResult::success()
            ->toast(__('tenant.members.role-updated'), Variant::Success)
            ->reloadComponent('tenants.members');
    }
}
