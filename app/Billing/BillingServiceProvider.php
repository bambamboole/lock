<?php

declare(strict_types=1);

namespace App\Billing;

use App\Billing\Http\Middleware\ShareSubscriptionCallout;
use App\Billing\Ui\Settings\BillingTab;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Cashier;
use Lattice\Core\Facades\Lattice;

final class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cashier::keepPastDueSubscriptionsActive();

        Lattice::extend('tenant.settings.tabs', (new BillingTab)(...), priority: 35);

        $this->callAfterResolving(Kernel::class, function (Kernel $kernel): void {
            if ($kernel instanceof HttpKernel) {
                $kernel->appendMiddlewareToGroup('web', ShareSubscriptionCallout::class);
            }
        });
    }
}
