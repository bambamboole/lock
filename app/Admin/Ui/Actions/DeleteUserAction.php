<?php
declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Contracts\DeletesUser;
use App\Shared\Concerns\ResolvesCurrentUser;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.delete', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class DeleteUserAction extends ActionDefinition
{
    use ResolvesCurrentUser;

    public function __construct(private readonly DeletesUser $deleteUser) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.delete.label'))
            ->method(HttpMethod::Delete)
            ->confirm(__('admin.users.detail.delete.confirm.title'), __('admin.users.detail.delete.confirm.description'));
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $target = $this->contextStringOrNull('user');

        return $target !== null && $target !== $this->currentUser()->id;
    }

    public function handle(Request $request): ActionResult
    {
        /** @var User $user */
        $user = $this->contextModel('user');
        $realm = $user->realm;

        Audit::record(AdminAuditEvent::UserDeleted, $user, properties: [
            'name' => $user->name,
            'email' => $user->email,
            'realm' => $realm->slug,
        ]);

        $this->deleteUser->handle($user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.delete.done'), Variant::Success)
            ->toRoute('admin.realms.users', ['realm' => $realm->slug]);
    }
}
