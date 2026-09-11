<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Actions\UnblockUser;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.unblock', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UnblockUserAction extends ActionDefinition
{
    public function __construct(private readonly UnblockUser $unblockUser) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.unblock.label'))
            ->confirm(__('admin.users.detail.unblock.confirm.title'), __('admin.users.detail.unblock.confirm.description'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);

        abort_unless($user->isBlocked(), 422, __('admin.users.not-blocked'));

        $this->unblockUser->handle($user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.unblock.done'), Variant::Success)
            ->reloadPage();
    }
}
