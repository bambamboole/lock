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

#[AsAction('billing.subscription.update-payment-method', can: TenantPermission::ManageBilling, on: 'tenant')]
final class UpdatePaymentMethod extends ActionDefinition
{
    use ActivatesTenantContext;
    use HandlesPaddleFailures;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('billing.manage.update-payment-method'))
            ->method(HttpMethod::Post)
            ->emphasis(Emphasis::Outline);
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $subscription = $tenant->subscription();

        if ($subscription === null) {
            return ActionResult::failure(__('billing.manage.no-subscription'));
        }

        return $this->attemptPaddleAction(fn () => ActionResult::success()->to($subscription->paymentMethodUpdateUrl()));
    }
}
