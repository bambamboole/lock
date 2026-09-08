<?php
declare(strict_types=1);

use App\Admin\Console\Commands\GrantAdminCommand;
use App\Auth\Console\Commands\SyncPermissionsCommand;
use App\Billing\Http\Middleware\ThrottleApiRequests;
use App\Shared\Console\Commands\AppInstallCommand;
use App\Shared\Console\Commands\DeployCommand;
use App\Shared\Exceptions\MissingTenantContext;
use App\Shared\Http\Middleware\HandleAppearance;
use App\Shared\Http\Middleware\HandleInertiaRequests;
use App\Tenants\Http\Middleware\ResolveApiTenant;
use App\Tenants\Http\Middleware\SetTenantUrlDefaults;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a TLS-terminating reverse proxy, generated asset and route
        // URLs are http:// without this and the browser blocks them as mixed
        // content. Only safe while the app is exclusively reachable through
        // the proxy — narrow it to the proxy's address otherwise.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance']);

        /*
         * Already-authenticated users hitting a guest route must not be sent to
         * the framework's route('dashboard') probe: that route is tenant-scoped
         * ({current_tenant}/dashboard) and has no default for the parameter here.
         * /dashboard (dashboard.home) resolves the tenant and forwards.
         */
        $middleware->redirectUsersTo('/dashboard');

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTenantUrlDefaults::class,
        ]);

        $middleware->alias([
            'throttle.api' => ThrottleApiRequests::class,
            // The framework default redirects to verification.notice, which no
            // route registers — the OIDC package names its verification prompt
            // identity.verification.notice.
            'verified' => EnsureEmailIsVerified::redirectTo('identity.verification.notice'),
        ]);

        // Route model binding on a BusinessModel needs the tenant context
        // ResolveApiTenant sets, so it must run before SubstituteBindings — not
        // just after auth, where the api group's default order would place it.
        $middleware->appendToPriorityList(
            after: AuthenticatesRequests::class,
            append: ResolveApiTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A BusinessModel query without a current tenant throws with a
        // developer-facing message — on token-facing paths that is an
        // authorization problem, not a server error.
        $exceptions->render(fn (MissingTenantContext $exception, Request $request) => $request->is('api/*')
            ? response()->json(['message' => __('tenant.api.no-tenant-context')], 403)
            : null);
    })
    ->withCommands([
        AppInstallCommand::class,
        DeployCommand::class,
        GrantAdminCommand::class,
        SyncPermissionsCommand::class,
    ])->create();
