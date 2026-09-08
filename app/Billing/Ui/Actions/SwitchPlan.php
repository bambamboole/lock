<?php

declare(strict_types=1);

namespace App\Billing\Ui\Actions;

use App\Billing\Data\PlanPricing;
use App\Billing\Services\PricingService;
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

#[AsAction('billing.subscription.switch-plan', can: TenantPermission::ManageBilling, on: 'tenant')]
final class SwitchPlan extends ActionDefinition
{
    use ActivatesTenantContext;
    use HandlesPaddleFailures;

    public function definition(Action $action): Action
    {
        $action = $action->method(HttpMethod::Post)->emphasis(Emphasis::Outline);

        $target = $this->findTargetPlan();

        if ($target === null) {
            return $action;
        }

        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $prefix = $this->isUpgrade($tenant, $target) ? 'upgrade' : 'downgrade';

        return $action
            ->label(__("billing.switch.{$prefix}.label", ['plan' => $target->name]))
            ->confirm(
                title: __("billing.switch.{$prefix}.confirm.title", ['plan' => $target->name]),
                description: __("billing.switch.{$prefix}.confirm.description"),
                confirmLabel: __("billing.switch.{$prefix}.label", ['plan' => $target->name]),
            );
    }

    public function handle(Request $request): ActionResult
    {
        /** @var Tenant $tenant */
        $tenant = $this->contextModel('tenant');
        $subscription = $tenant->subscription();
        $target = $this->targetPlan();

        if ($subscription === null || $target->paddlePriceId === null) {
            return ActionResult::failure(__('billing.switch.unavailable'));
        }

        return $this->attemptPaddleAction(function () use ($tenant, $subscription, $target) {
            if ($this->isUpgrade($tenant, $target)) {
                $subscription->swapAndInvoice($target->paddlePriceId);
            } else {
                $subscription->swap($target->paddlePriceId);
            }

            return ActionResult::success()
                ->toast(__('billing.switch.switched', ['plan' => $target->name]), Variant::Success)
                ->reloadPage();
        });
    }

    private function targetPlan(): PlanPricing
    {
        $plans = app(PricingService::class)->plans();
        $key = $this->contextString('plan');

        abort_unless(isset($plans[$key]) && $plans[$key]->isPaid(), 404);

        return $plans[$key];
    }

    private function findTargetPlan(): ?PlanPricing
    {
        $plans = app(PricingService::class)->plans();
        $plan = $plans[$this->contextString('plan')] ?? null;

        return $plan?->isPaid() ? $plan : null;
    }

    private function isUpgrade(Tenant $tenant, PlanPricing $target): bool
    {
        $current = app(PricingService::class)->plan($tenant->currentPlanName());

        return $target->rawPrice > $current->rawPrice;
    }
}
