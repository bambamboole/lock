<?php
declare(strict_types=1);

namespace App\Roles\Ui\Actions;

use App\Roles\Ui\Concerns\BuildsRoleFields;
use App\Roles\Ui\Concerns\ResolvesRole;
use App\Roles\Ui\Tables\RolesTable;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Roles\Contracts\CreatesRole;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.realm-roles.create', can: Scope::RolesWrite)]
final class CreateRoleAction extends ActionDefinition
{
    use BuildsRoleFields;
    use ResolvesRole;

    public function __construct(private readonly CreatesRole $createRole) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.roles.create.label'))
            ->form($this->realmRoleFields($this->realm(), null));
    }

    public function handle(FormData $data): ActionResult
    {
        $description = trim((string) $data->string('description'));

        $this->createRole->handle(
            $this->realm(),
            (string) $data->string('name'),
            $description === '' ? null : $description,
            $this->submittedScopeIds($this->realm(), $data->get('scope_ids', [])),
        );

        return ActionResult::success()
            ->toast(__('admin.roles.created'), Variant::Success)
            ->reloadComponent(RolesTable::ID);
    }
}
