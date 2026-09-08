<?php

declare(strict_types=1);

namespace App\Billing\Ui\Settings;

use App\Auth\Models\User;
use App\Billing\Data\PlanPricing;
use App\Billing\Enums\SubscriptionState;
use App\Billing\Services\PricingService;
use App\Billing\Ui\Actions\CancelSubscription;
use App\Billing\Ui\Actions\ResumeSubscription;
use App\Billing\Ui\Actions\SwitchPlan;
use App\Billing\Ui\Components\CheckoutButton;
use App\Billing\Ui\Tables\TransactionsTable;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use Lattice\Actions\Components\Action;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Components\Tab;
use Lattice\Ui\Components\Text;

final class BillingTab
{
    public function __invoke(Tenant $tenant, User $user, PricingService $pricing): Tab
    {
        $canManage = $user->can(TenantPermission::ManageBilling, $tenant);

        return Tab::make('billing', __('tenant.settings.tabs.billing'))
            ->schema([
                Card::make(__('billing.heading'), __('billing.subheading'))
                    ->schema([
                        Grid::make('billing-plans')->columns(3)->schema(
                            array_map(
                                fn (PlanPricing $plan): Card => $this->planCard($plan, $tenant, $canManage),
                                array_values($pricing->plans('monthly')),
                            ),
                        ),
                    ]),
                $this->manageSubscriptionCard($tenant)
                    ->visible($tenant->subscribed() && $canManage),
                Card::make(__('billing.transactions.heading'))
                    ->visible($canManage)
                    ->schema([
                        Table::use(TransactionsTable::class),
                    ]),
            ]);
    }

    private function planCard(PlanPricing $plan, Tenant $tenant, bool $canManage): Card
    {
        $isCurrent = $plan->key === $tenant->currentPlanName();
        $state = SubscriptionState::for($tenant);

        $schema = [
            Text::make($plan->formattedPrice),
            $isCurrent
                ? Badge::make($this->planBadge($state, $tenant))
                : Text::make(implode(', ', $plan->features())),
        ];

        $action = $isCurrent ? null : $this->planAction($plan, $tenant, $state, $canManage);

        if ($action instanceof Component) {
            $schema[] = $action;
        }

        return Card::make($plan->name, $plan->description)->schema($schema);
    }

    private function planAction(PlanPricing $plan, Tenant $tenant, SubscriptionState $state, bool $canManage): ?Component
    {
        if (! $plan->isPaid() || $plan->paddlePriceId === null || ! $canManage) {
            return null;
        }

        return match ($state) {
            SubscriptionState::Active, SubscriptionState::PastDue => Action::use(SwitchPlan::class, [
                'plan' => $plan->key,
            ]),
            SubscriptionState::Canceling => null,
            default => CheckoutButton::make(
                label: __('billing.subscribe'),
                checkoutUrl: route('billing.checkout', ['current_tenant' => $tenant->slug]),
                priceId: $plan->paddlePriceId,
                token: (string) config('cashier.client_side_token'),
                sandbox: (bool) config('cashier.sandbox'),
            ),
        };
    }

    private function manageSubscriptionCard(Tenant $tenant): Card
    {
        return Card::make(__('billing.manage.heading'))->schema([
            Action::use(CancelSubscription::class),
            Action::use(ResumeSubscription::class),
        ]);
    }

    private function planBadge(SubscriptionState $state, Tenant $tenant): string
    {
        return match ($state) {
            SubscriptionState::PastDue => __('billing.state.past-due.badge'),
            SubscriptionState::Canceling => __('billing.state.canceling.badge', [
                'date' => $tenant->subscription()?->ends_at?->translatedFormat('j F Y') ?? '',
            ]),
            default => __('billing.current-plan'),
        };
    }
}
