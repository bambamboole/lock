<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\ResetsUserMfa;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.reset-mfa', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class ResetUserMfaAction extends ActionDefinition
{
    public function __construct(private readonly ResetsUserMfa $resetMfa) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.reset-mfa.label'))
            ->confirm(__('admin.users.detail.reset-mfa.confirm.title'), __('admin.users.detail.reset-mfa.confirm.description'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);

        $this->resetMfa->handle($user);

        Audit::record(AdminAuditEvent::UserMfaReset, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.reset-mfa.done'), Variant::Success)
            ->reloadPage();
    }
}
