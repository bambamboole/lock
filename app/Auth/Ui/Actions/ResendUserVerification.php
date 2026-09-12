<?php

declare(strict_types=1);

namespace App\Auth\Ui\Actions;

use App\Auth\Models\User;
use App\Shared\Audit\Audit;
use App\Shared\Auth\Enums\UserAuditEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.resend-verification', can: Scope::UsersWrite)]
final class ResendUserVerification extends ActionDefinition
{
    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.resend-verification'));
    }

    public function handle(Request $request): ActionResult
    {
        $user = $this->contextModel('user', User::class);

        abort_if($user->hasVerifiedEmail(), 422, __('admin.users.already-verified'));

        // The verification link is minted for the current realm's host.
        $user->realm->runAsCurrent(fn () => $user->sendEmailVerificationNotification());

        Audit::record(UserAuditEvent::VerificationResent, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.verification-sent'), Variant::Success);
    }
}
