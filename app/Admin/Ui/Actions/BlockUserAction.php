<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Actions\BlockUser;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Shared\Concerns\ResolvesCurrentUser;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.block', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class BlockUserAction extends ActionDefinition
{
    use ResolvesCurrentUser;

    public function __construct(private readonly BlockUser $blockUser) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.block.label'))
            ->variant(Variant::Danger)
            ->emphasis(Emphasis::Outline)
            ->confirm(__('admin.users.detail.block.confirm.title'), __('admin.users.detail.block.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $target = $this->contextModelOrNull('user');

        return $target instanceof User && $target->isNot($this->currentUser());
    }

    public function handle(Request $request): ActionResult
    {
        /** @var User $user */
        $user = $this->contextModel('user');

        abort_if($user->isBlocked(), 422, __('admin.users.already-blocked'));

        $this->blockUser->handle($user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.block.done'), Variant::Success)
            ->reloadPage();
    }
}
