<?php
declare(strict_types=1);

use App\Realms\Http\Api\V1\Controllers\RealmController;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Http\Middleware\EnsureManagementApiAudience;
use Bambamboole\LaravelOidc\Server\Tokens\Http\Middleware\CheckScopes;
use Illuminate\Support\Facades\Route;

/*
 * The management API is the master realm's protected resource, so it only
 * answers on the application host — RealmHostGate turns every other host away.
 * Authorization is scope only: a `client_credentials` token has no user behind
 * it, and nothing here reads one.
 */
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:oidc', EnsureManagementApiAudience::class])
    ->group(function (): void {
        Route::apiResource('realms', RealmController::class)
            ->middlewareFor(['index', 'show'], CheckScopes::using(ApiScope::RealmsRead->value))
            ->middlewareFor(['store', 'update', 'destroy'], CheckScopes::using(ApiScope::RealmsWrite->value));
    });
