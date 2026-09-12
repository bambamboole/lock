<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Ui\Concerns\ResolvesRealmRole;
use App\Admin\Ui\Tables\AdminRealmRolesTable;
use App\Realms\Models\RealmRole;
use App\Shared\Admin\Enums\AdminPermission;
use App\Shared\Realms\Contracts\DeletesRealmRole;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realm-roles.delete', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class DeleteRealmRoleAction extends ActionDefinition
{
    use ResolvesRealmRole;

    public function __construct(private readonly DeletesRealmRole $deleteRole) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.roles.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->emphasis(Emphasis::Ghost)
            ->confirm(__('admin.roles.delete.confirm.title'), __('admin.roles.delete.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return $this->realmRoleOrNull() instanceof RealmRole;
    }

    public function handle(Request $request): ActionResult
    {
        $this->deleteRole->handle($this->realmRole());

        return ActionResult::success()
            ->toast(__('admin.roles.deleted'), Variant::Success)
            ->reloadComponent(AdminRealmRolesTable::ID);
    }
}
