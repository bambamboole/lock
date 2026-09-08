<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Realms\Actions\CancelInvitation as CancelInvitationAction;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('realms.invitations.cancel', can: RealmPermission::ManageMembers, on: 'realm')]
final class CancelInvitation extends ActionDefinition
{
    use ActivatesRealmContext;

    public function __construct(private readonly CancelInvitationAction $cancelInvitation) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('realm.invitations.cancel.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('realm.invitations.cancel.confirm.title'),
                description: __('realm.invitations.cancel.confirm.description'),
                confirmLabel: __('realm.invitations.cancel.confirm.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var RealmInvitation $invitation */
        $invitation = $this->contextModel('invitation');

        $this->cancelInvitation->handle($realm, $invitation);

        return ActionResult::success()
            ->toast(__('realm.invitations.cancelled'), Variant::Success)
            ->reloadComponent('realms.invitations');
    }
}
