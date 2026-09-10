<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Tables\UserSessionsTable;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\EndsUserSessions;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.sessions.end-all', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
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
        /** @var User $user */
        $user = $this->contextModel('user');

        $ended = $this->endSessions->handle($user);

        Audit::record(AdminAuditEvent::UserSessionsEnded, $user, properties: ['count' => $ended]);

        return ActionResult::success()
            ->toast(__('admin.users.detail.sessions.end-all.done', ['count' => $ended]), Variant::Success)
            ->reloadComponent(UserSessionsTable::ID);
    }
}
