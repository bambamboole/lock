<?php
declare(strict_types=1);

namespace App\Realms\Ui\Actions;

use App\Realms\Actions\ResendInvitation as ResendInvitationAction;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmInvitation;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Shared\Concerns\ResolvesCurrentUser;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('realms.invitations.resend', can: RealmPermission::ManageMembers, on: 'realm')]
final class ResendInvitation extends ActionDefinition
{
    use ActivatesRealmContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly ResendInvitationAction $resendInvitation) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('realm.invitations.resend'))
            ->method(HttpMethod::Post)
            ->variant(Variant::Secondary);
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');
        /** @var RealmInvitation $invitation */
        $invitation = $this->contextModel('invitation');

        $this->resendInvitation->handle(
            $realm,
            $this->currentUser(),
            $invitation,
        );

        return ActionResult::success()
            ->toast(__('realm.invitations.resent'), Variant::Success)
            ->reloadComponent('realms.invitations');
    }
}
