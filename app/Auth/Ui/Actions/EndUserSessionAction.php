<?php
declare(strict_types=1);

namespace App\Auth\Ui\Actions;

use App\Auth\Models\User;
use App\Auth\Ui\Tables\UserSessionsTable;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\EndsUserSessions;
use App\Shared\Auth\Enums\UserAdminEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.sessions.end', can: Scope::UsersWrite)]
final class EndUserSessionAction extends ActionDefinition
{
    public function __construct(private readonly EndsUserSessions $endSessions) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.sessions.end.label'))
            ->variant(Variant::Danger)
            ->emphasis(Emphasis::Ghost)
            ->confirm(__('admin.users.detail.sessions.end.confirm.title'), __('admin.users.detail.sessions.end.confirm.description'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);
        $sid = $this->contextString('sid');

        $ended = $this->endSessions->handle($user, $sid);

        abort_if($ended === 0, 422, __('admin.users.detail.sessions.not-active'));

        Audit::record(UserAdminEvent::UserSessionEnded, $user, context: ['sid' => $sid]);

        return ActionResult::success()
            ->toast(__('admin.users.detail.sessions.end.done'), Variant::Success)
            ->reloadComponent(UserSessionsTable::ID);
    }
}
