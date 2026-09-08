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
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('billing.subscription.cancel', can: TenantPermission::ManageBilling, on: 'tenant')]
final class CancelSubscription extends ActionDefinition
{
    use ActivatesTenantContext;
    use HandlesPaddleFailures;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('billing.manage.cancel.label'))
            ->method(HttpMethod::Post)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('billing.manage.cancel.confirm.title'),
                description: __('billing.manage.cancel.confirm.description'),
                confirmLabel: __('billing.manage.cancel.label'),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $subscription = $tenant->subscription();

        if ($subscription === null || ! $subscription->active()) {
            return ActionResult::failure(__('billing.manage.no-subscription'));
        }

        return $this->attemptPaddleAction(function () use ($subscription) {
            $subscription->cancel();

            return ActionResult::success()
                ->toast(__('billing.manage.canceled'), Variant::Success)
                ->reloadPage();
        });
    }
}
