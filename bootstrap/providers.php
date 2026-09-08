<?php
declare(strict_types=1);

use App\Auth\AuthServiceProvider;
use App\Realms\RealmsServiceProvider;
use App\Shared\AppServiceProvider;

return [
    AppServiceProvider::class,
    RealmsServiceProvider::class,
    AuthServiceProvider::class,
];
