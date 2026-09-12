<?php
declare(strict_types=1);

use App\Admin\AdminServiceProvider;
use App\Auth\AuthServiceProvider;
use App\Clients\ClientsServiceProvider;
use App\Realms\RealmsServiceProvider;
use App\Resources\ResourcesServiceProvider;
use App\Shared\AppServiceProvider;

return [
    AppServiceProvider::class,
    RealmsServiceProvider::class,
    ClientsServiceProvider::class,
    AuthServiceProvider::class,
    ResourcesServiceProvider::class,
    AdminServiceProvider::class,
];
