<?php
declare(strict_types=1);

namespace App\Auth\Ui\Actions;

use App\Auth\Models\User;
use App\Auth\Ui\Tables\UserSessionsTable;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\EndsUserSessions;
use App\Shared\Auth\Enums\UserAuditEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.sessions.end-all', can: Scope::UsersWrite)]
final class EndAllUserSessionsAction extends ActionDefinition
{
    public function __construct(private readonly EndsUserSessions $endSessions) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.sessions.end-all.label'))
            ->confirm(__('admin.users.detail.sessions.end-all.confirm.title'), __('admin.users.detail.sessions.end-all.confirm.description'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);

        $ended = $this->endSessions->handle($user);

        Audit::record(UserAuditEvent::UserSessionsEnded, $user, properties: ['count' => $ended]);

        return ActionResult::success()
            ->toast(__('admin.users.detail.sessions.end-all.done', ['count' => $ended]), Variant::Success)
            ->reloadComponent(UserSessionsTable::ID);
    }
}
