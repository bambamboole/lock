<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Concerns\BuildsRealmRoleFields;
use App\Admin\Ui\Concerns\ResolvesRealmRole;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Realms\Models\RealmRole;
use App\Shared\Realms\Contracts\UpdatesRealmRole;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realm-roles.update', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UpdateRealmRoleAction extends ActionDefinition
{
    use BuildsRealmRoleFields;
    use ResolvesRealmRole;

    public function __construct(private readonly UpdatesRealmRole $updateRole) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.roles.edit.label'))
            ->method(HttpMethod::Patch)
            ->emphasis(Emphasis::Ghost)
            ->form($this->realmRoleFields($this->realm(), $this->realmRole()));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->realmRoleOrNull() instanceof RealmRole;
    }

    public function handle(FormData $data): ActionResult
    {
        $description = trim((string) $data->string('description'));

        $this->updateRole->handle($this->realmRole(), (string) $data->string('name'), $description === '' ? null : $description);

        return ActionResult::success()
            ->toast(__('admin.roles.updated'), Variant::Success)
            ->reloadComponent(AdminRealmRolesTable::ID);
    }
}
