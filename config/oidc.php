<?php
declare(strict_types=1);

use App\Realms\Http\Middleware\SetCurrentRealm;
use App\Resources\Support\ResourceScopeCatalog;
use App\Shared\Audit\Support\DatabaseAuditSink;

return [
    // Null, so the origin comes from `app.url`: every realm host shares its
    // scheme and port.
    'issuer' => null,

    'routes' => [
        // The protocol endpoints outside the web group mint URLs too (the
        // discovery document), and those have to name the realm's own host.
        'middleware' => [SetCurrentRealm::class],
        'realms' => 'domain',
    ],

    // mergeConfigFrom only merges the top level, so naming a sink here drops
    // the package's other audit keys unless they are repeated.
    'audit' => [
        'enabled' => env('OIDC_AUDIT_ENABLED', true),
        'sink' => DatabaseAuditSink::class,
        'log_channel' => env('OIDC_AUDIT_LOG_CHANNEL'),
    ],

    // A scope belongs to exactly one resource, so the catalog is read per
    // requested RFC 8707 `resource`.
    'scopes' => ResourceScopeCatalog::class,
];
