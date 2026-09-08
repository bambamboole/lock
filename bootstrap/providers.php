<?php
declare(strict_types=1);

use App\Admin\AdminServiceProvider;
use App\Auth\AuthServiceProvider;
use App\Realms\RealmsServiceProvider;
use App\Shared\AppServiceProvider;

return [
    AppServiceProvider::class,
    RealmsServiceProvider::class,
    AuthServiceProvider::class,
    AdminServiceProvider::class,
];
