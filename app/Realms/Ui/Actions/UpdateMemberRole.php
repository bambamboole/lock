<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Auth\Models\User;
use App\Realms\Actions\UpdateMember;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\FormData;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('realms.members.update', can: RealmPermission::ManageMembers, on: 'realm')]
final class UpdateMemberRole extends ActionDefinition
{
    use ActivatesRealmContext;

    public function __construct(private readonly UpdateMember $updateMember) {}

    public function definition(Action $action): Action
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        return $action
            ->label(__('realm.members.change-role'))
            ->method(HttpMethod::Patch)
            ->variant(Variant::Secondary)
            ->form([
                CheckboxGroup::make('role_ids', __('common.field.role'))
                    ->options(
                        $realm->assignableRoles()
                            ->map(fn (Role $role) => CheckboxGroup::option($role->displayName(), (string) $role->id))
                            ->all(),
                    )
                    ->rules(['min:1'])
                    ->required(),
            ]);
    }

    public function handle(FormData $data): ActionResult
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var User $member */
        $member = $this->contextModel('member');

        $this->updateMember->handle(
            $realm,
            $member,
            array_values((array) $data['role_ids']),
        );

        return ActionResult::success()
            ->toast(__('realm.members.role-updated'), Variant::Success)
            ->reloadComponent('realms.members');
    }
}
