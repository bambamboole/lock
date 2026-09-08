<?php

declare(strict_types=1);

namespace App\Admin\Ui\Actions;

use App\Admin\Enums\AdminAuditEvent;
use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Tenants\Support\Audit;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Variant;

#[AsAction('admin.users.resend-verification', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class ResendUserVerification extends ActionDefinition
{
    public function definition(Action $action): Action
    {
        return $action
            ->label(__('admin.users.detail.resend-verification'))
            ->variant(Variant::Secondary);
    }

    public function handle(Request $request): ActionResult
    {
        /** @var User $user */
        $user = $this->contextModel('user');

        abort_if($user->hasVerifiedEmail(), 422, __('admin.users.already-verified'));

        $user->sendEmailVerificationNotification();

        Audit::record(AdminAuditEvent::VerificationResent, $user);

        return ActionResult::success()
            ->toast(__('admin.users.detail.verification-sent'), Variant::Success);
    }
}
