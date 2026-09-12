<?php

declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Models\Role;
use App\Auth\Models\User;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Audit\Audit;
use App\Shared\Concerns\ResolvesCurrentUser;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.roles.update', can: [AdminPermission::Access, AdminPermission::ManageAdminRoles])]
final class UpdateUserGlobalRoles extends ActionDefinition
{
    use ResolvesCurrentUser;

    public function definition(Action $action): Action
    {
        $target = $this->contextModel('user', User::class);

        return $action
            ->label(__('admin.users.detail.update-roles'))
            ->method(HttpMethod::Patch)
            ->form([
                CheckboxGroup::make('role_ids', __('admin.users.detail.roles.field.label'))
                    ->options(
                        Role::query()
                            ->get()
                            ->map(fn (Role $role) => CheckboxGroup::option($role->name, (string) $role->id))
                            ->all(),
                    )
                    ->value($target->roles()->pluck('roles.id')->all(), editable: true),
            ]);
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $target = $this->contextModelOrNull('user', User::class);

        return $target instanceof User && $target->realm->isMaster() && $target->isNot($this->currentUser());
    }

    public function handle(FormData $data): ActionResult
    {
        $target = $this->contextModel('user', User::class);

        $globalRoleIds = Role::query()
            ->whereIn('id', array_map(strval(...), (array) ($data['role_ids'] ?? [])))
            ->pluck('id');

        $before = $target->roles()->get();
        $after = Role::query()->whereIn('id', $globalRoleIds)->get();

        $added = $after->whereNotIn('id', $before->pluck('id'))->map(fn (Role $role) => $role->name)->values()->all();
        $removed = $before->whereNotIn('id', $after->pluck('id'))->map(fn (Role $role) => $role->name)->values()->all();

        $target->roles()->sync($globalRoleIds);

        if ($added !== [] || $removed !== []) {
            Audit::record(
                AdminAuditEvent::GlobalRolesUpdated,
                $target,
                properties: ['added' => $added, 'removed' => $removed],
            );
        }

        return ActionResult::success()
            ->toast(__('admin.users.detail.roles-updated'), Variant::Success)
            ->reloadPage();
    }
}
