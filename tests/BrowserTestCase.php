<?php

declare(strict_types=1);

namespace Tests;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response as ServerResponse;
use Amp\Http\Server\SocketHttpServer;
use Illuminate\Http\Request;
use Pest\Browser\Drivers\LaravelHttpServer;
use Pest\Browser\Playwright\Playwright;
use Pest\Browser\ServerManager;
use Psr\Log\NullLogger;
use ReflectionMethod;
use ReflectionProperty;

abstract class BrowserTestCase extends TestCase
{
    protected bool $stubsVite = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startBrowserHttpServerWithLongKeepAlive();
        $this->serveTheMasterRealmFromTheBrowserServer();
    }

    protected function tearDown(): void
    {
        Playwright::setHost(null);

        parent::tearDown();
    }

    /**
     * The plugin points `app.url` at its own server, which makes that server's
     * host the master realm's; what a test runs in-process has to resolve the
     * realm from the same host.
     */
    private function serveTheMasterRealmFromTheBrowserServer(): void
    {
        ServerManager::instance()->http()->bootstrap();

        app()->instance('request', Request::create((string) config('app.url')));
    }

    /**
     * Chrome pools connections for minutes and ignores the server's
     * `Keep-Alive: timeout`, so with amphp's 15-second default it sends
     * requests on connections the server is closing: dynamic imports fail and
     * Inertia submits vanish without an error. The plugin exposes no server
     * configuration, so this pre-starts the identical server with an hour-long
     * timeout, and LaravelHttpServer::start() then leaves the socket in place.
     * Ported from lattice-php/lattice#493.
     */
    private function startBrowserHttpServerWithLongKeepAlive(): void
    {
        $http = ServerManager::instance()->http();

        if (! $http instanceof LaravelHttpServer) {
            return;
        }

        $socketProperty = new ReflectionProperty(LaravelHttpServer::class, 'socket');

        if ($socketProperty->getValue($http) !== null) {
            return;
        }

        $logger = new NullLogger;
        $keepAliveSeconds = 3600;

        $server = SocketHttpServer::createForDirectAccess(
            $logger,
            httpDriverFactory: new DefaultHttpDriverFactory(
                $logger,
                streamTimeout: $keepAliveSeconds,
                connectionTimeout: $keepAliveSeconds,
            ),
        );

        $server->expose("{$http->host}:{$http->port}");

        $handleRequest = new ReflectionMethod(LaravelHttpServer::class, 'handleRequest');

        $server->start(
            new ClosureRequestHandler(function (ServerRequest $request) use ($handleRequest, $http): ServerResponse {
                $response = $handleRequest->invoke($http, $request);

                assert($response instanceof ServerResponse);

                return $response;
            }),
            new DefaultErrorHandler,
        );

        $socketProperty->setValue($http, $server);
    }
}
