<?php
declare(strict_types=1);

namespace App\Roles\Ui\Actions;

use App\Roles\Models\Role;
use App\Roles\Ui\Concerns\ResolvesRole;
use App\Roles\Ui\Tables\RolesTable;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Roles\Contracts\DeletesRole;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realm-roles.delete', can: ApiScope::RolesWrite)]
final class DeleteRoleAction extends ActionDefinition
{
    use ResolvesRole;

    public function __construct(private readonly DeletesRole $deleteRole) {}

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
        return $this->mutableRoleOrNull() instanceof Role;
    }

    public function handle(Request $request): ActionResult
    {
        $this->deleteRole->handle($this->mutableRole());

        return ActionResult::success()
            ->toast(__('admin.roles.deleted'), Variant::Success)
            ->reloadComponent(RolesTable::ID);
    }
}
