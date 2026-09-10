<?php

declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.send-password-reset', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class SendUserPasswordReset extends ActionDefinition
{
    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.send-password-reset'));
    }

    public function handle(Request $request): ActionResult
    {
        /** @var User $user */
        $user = $this->contextModel('user');

        /*
         * Admin spans realms, but credential lookup is realm-local — the broker
         * would not find a user outside the administrator's own realm.
         */
        $user->realm->runAsCurrent(fn () => Password::broker(config('auth.defaults.passwords', 'users'))
            ->sendResetLink(['email' => $user->email]));

        Audit::record(AdminAuditEvent::PasswordResetSent, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.password-reset-sent'), Variant::Success);
    }
}
