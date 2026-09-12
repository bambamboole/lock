<?php

declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Audit\Audit;
use Bambamboole\LaravelOidc\Server\Authentication\Actions\SendPasswordResetLink;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.send-password-reset', can: ApiScope::UsersWrite)]
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

        Audit::record(AdminAuditEvent::PasswordResetSent, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.password-reset-sent'), Variant::Success);
    }
}
