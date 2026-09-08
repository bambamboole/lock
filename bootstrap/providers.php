<?php
declare(strict_types=1);

use App\Auth\AuthServiceProvider;
use App\Billing\BillingServiceProvider;
use App\Shared\AppServiceProvider;
use App\Tenants\TenantsServiceProvider;

return [
    AppServiceProvider::class,
    TenantsServiceProvider::class,
    BillingServiceProvider::class,
    AuthServiceProvider::class,
];
