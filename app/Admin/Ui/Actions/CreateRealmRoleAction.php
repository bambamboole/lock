<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Ui\Concerns\BuildsRealmRoleFields;
use App\Admin\Ui\Concerns\ResolvesRealmRole;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Realms\Contracts\CreatesRealmRole;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realm-roles.create', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class CreateRealmRoleAction extends ActionDefinition
{
    use BuildsRealmRoleFields;
    use ResolvesRealmRole;

    public function __construct(private readonly CreatesRealmRole $createRole) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.roles.create.label'))
            ->form($this->realmRoleFields($this->realm(), null));
    }

    public function handle(FormData $data): ActionResult
    {
        $description = trim((string) $data->string('description'));

        $this->createRole->handle($this->realm(), (string) $data->string('name'), $description === '' ? null : $description);

        return ActionResult::success()
            ->toast(__('admin.roles.created'), Variant::Success)
            ->reloadComponent(AdminRealmRolesTable::ID);
    }
}
