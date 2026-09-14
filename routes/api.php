<?php
declare(strict_types=1);

use App\Clients\Http\Api\V1\Controllers\ClientController;
use App\Realms\Http\Api\V1\Controllers\RealmController;
use App\Realms\Http\Api\V1\Controllers\SocialProviderController;
use App\Resources\Http\Api\V1\Controllers\ResourceController;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Http\Middleware\EnsureManagementApiAudience;
use App\Shared\Http\Middleware\EnsureScopes;
use Illuminate\Support\Facades\Route;

/*
 * The management API is the master realm's protected resource, so it only
 * answers on the application host — RealmHostGate turns every other host away.
 * Authorization is scope only: a `client_credentials` token has no user behind
 * it, and nothing here reads one. When a person is behind the token,
 * EnsureScopes also intersects the required scopes with the ones their roles
 * still grant.
 */
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:oidc', EnsureManagementApiAudience::class])
    ->group(function (): void {
        Route::apiResource('realms', RealmController::class)
            ->middlewareFor(['index', 'show'], EnsureScopes::using(Scope::RealmsRead->value))
            ->middlewareFor(['store', 'update', 'destroy'], EnsureScopes::using(Scope::RealmsWrite->value));

        Route::apiResource('realms.social-providers', SocialProviderController::class)
            ->middlewareFor(['index', 'show'], EnsureScopes::using(Scope::RealmsRead->value))
            ->middlewareFor(['store', 'update', 'destroy'], EnsureScopes::using(Scope::RealmsWrite->value));

        Route::apiResource('realms.clients', ClientController::class)
            ->middlewareFor(['index', 'show'], EnsureScopes::using(Scope::ClientsRead->value))
            ->middlewareFor(['store', 'update', 'destroy'], EnsureScopes::using(Scope::ClientsWrite->value));

        Route::apiResource('realms.resources', ResourceController::class)
            ->middlewareFor(['index', 'show'], EnsureScopes::using(Scope::ResourcesRead->value))
            ->middlewareFor(['store', 'update', 'destroy'], EnsureScopes::using(Scope::ResourcesWrite->value));
    });
