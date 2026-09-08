<?php
declare(strict_types=1);

use App\Tenants\Enums\TenantPermission;
use App\Tenants\Http\Api\V1\Controllers\MembersController;
use App\Tenants\Http\Middleware\ResolveApiTenant;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::middleware(['auth:oidc', ResolveApiTenant::class, 'throttle.api'])
    ->prefix('v1')
    ->group(function () {
        Route::get('members', MembersController::class)
            ->middleware(CheckToken::using(TenantPermission::ViewMembers->value))
            ->name('api.v1.members');
    });
