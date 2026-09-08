<?php

declare(strict_types=1);

namespace App\Billing\Ui\Actions;

use App\Billing\Ui\Actions\Concerns\HandlesPaddleFailures;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use App\Tenants\Ui\Concerns\ActivatesTenantContext;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('billing.subscription.resume', can: TenantPermission::ManageBilling, on: 'tenant')]
final class ResumeSubscription extends ActionDefinition
{
    use ActivatesTenantContext;
    use HandlesPaddleFailures;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('billing.manage.resume'))
            ->method(HttpMethod::Post)
            ->emphasis(Emphasis::Outline);
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $subscription = $tenant->subscription();

        if ($subscription === null || ! ($subscription->onGracePeriod() || $subscription->paused())) {
            return ActionResult::failure(__('billing.manage.nothing-to-resume'));
        }

        return $this->attemptPaddleAction(function () use ($subscription) {
            if ($subscription->onGracePeriod()) {
                $subscription->stopCancelation();
            } else {
                $subscription->resume();
            }

            return ActionResult::success()
                ->toast(__('billing.manage.resumed'), Variant::Success)
                ->reloadPage();
        });
    }
}
