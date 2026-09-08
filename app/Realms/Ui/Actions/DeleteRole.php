<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Realms\Enums\RealmAuditEvent;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Shared\Audit\Audit;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('realms.roles.delete', can: RealmPermission::ManageRoles, on: 'realm')]
final class DeleteRole extends ActionDefinition
{
    use ActivatesRealmContext;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('realm.settings.roles.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('realm.settings.roles.delete.confirm.title'),
                description: __('realm.settings.roles.delete.confirm.description'),
                confirmLabel: __('realm.settings.roles.delete.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var Role $role */
        $role = $this->contextModel('role');

        abort_if($role->isOwner(), 403);

        if ($role->inUse()) {
            return ActionResult::failure(__('realm.settings.roles.in-use'));
        }

        Audit::record(RealmAuditEvent::RoleDeleted, $role, $realm, [
            'name' => $role->name,
        ]);

        $role->delete();

        return ActionResult::success()
            ->toast(__('realm.settings.roles.deleted'), Variant::Success)
            ->reloadComponent('realms.roles');
    }
}
