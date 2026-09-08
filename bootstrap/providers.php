<?php
declare(strict_types=1);

use App\Auth\AuthServiceProvider;
use App\Shared\AppServiceProvider;
use App\Tenants\TenantsServiceProvider;

return [
    AppServiceProvider::class,
    TenantsServiceProvider::class,
    AuthServiceProvider::class,
];
