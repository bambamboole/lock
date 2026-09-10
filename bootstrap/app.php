<?php
declare(strict_types=1);

use App\Admin\Console\Commands\GrantAdminCommand;
use App\Admin\Console\Commands\SyncPermissionsCommand;
use App\Realms\Http\Middleware\SetCurrentRealm;
use App\Shared\Console\Commands\DeployCommand;
use App\Shared\Exceptions\MissingRealmContext;
use App\Shared\Http\Middleware\HandleAppearance;
use App\Shared\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

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

        // The framework default probes route('dashboard'), which does not exist;
        // the home route sends admins and realm users to their own landing.
        $middleware->redirectUsersTo('/');

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetCurrentRealm::class,
        ]);

        $middleware->appendToPriorityList(after: StartSession::class, append: SetCurrentRealm::class);

        $middleware->alias([
            // The framework default redirects to verification.notice, which no
            // route registers — the OIDC package names its verification prompt
            // identity.verification.notice.
            'verified' => EnsureEmailIsVerified::redirectTo('identity.verification.notice'),
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->map(fn (AuthenticationException $exception): AuthenticationException => new AuthenticationException(
            $exception->getMessage(),
            array_map(fn (?string $guard): string => $guard ?? auth()->getDefaultDriver(), $exception->guards()),
            $exception->redirectTo(request()),
        ));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A BusinessModel query without a current realm throws with a
        // developer-facing message — on token-facing paths that is an
        // authorization problem, not a server error.
        $exceptions->render(fn (MissingRealmContext $exception, Request $request) => $request->is('api/*')
            ? response()->json(['message' => __('common.api.no-realm-context')], 403)
            : null);
    })
    ->withCommands([
        DeployCommand::class,
        GrantAdminCommand::class,
        SyncPermissionsCommand::class,
    ])->create();
