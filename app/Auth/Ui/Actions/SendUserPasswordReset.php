<?php

declare(strict_types=1);

namespace App\Auth\Ui\Actions;

use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Enums\UserAdminEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;
use Lock\Server\Authentication\Actions\SendPasswordResetLink;

#[AsAction('admin.users.send-password-reset', can: Scope::UsersWrite)]
final class SendUserPasswordReset extends ActionDefinition
{
    public function __construct(private readonly SendPasswordResetLink $sendResetLink) {}

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.send-password-reset'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);

        $user->realm->runAsCurrent(fn (): string => ($this->sendResetLink)($user->email));

        Audit::record(UserAdminEvent::PasswordResetSent, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.password-reset-sent'), Variant::Success);
    }
}
