<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Auth\Models\User;
use App\Realms\Actions\RemoveMember as RemoveMemberAction;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('realms.members.remove', can: RealmPermission::ManageMembers, on: 'realm')]
final class RemoveMember extends ActionDefinition
{
    use ActivatesRealmContext;

    public function __construct(private readonly RemoveMemberAction $removeMember) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('realm.members.remove.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('realm.members.remove.confirm.title'),
                description: __('realm.members.remove.confirm.description'),
                confirmLabel: __('realm.members.remove.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var User $member */
        $member = $this->contextModel('member');

        $this->removeMember->handle($realm, $member);

        return ActionResult::success()
            ->toast(__('realm.members.removed'), Variant::Success)
            ->reloadComponent('realms.members');
    }
}
