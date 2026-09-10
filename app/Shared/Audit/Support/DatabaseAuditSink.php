<?php

declare(strict_types=1);

namespace App\Shared\Audit\Support;

use App\Realms\Models\Realm;
use App\Shared\Audit\Models\AuditEvent;
use Bambamboole\LaravelOidc\Server\Shared\Audit\AuditRecord;
use Bambamboole\LaravelOidc\Server\Shared\Audit\AuditSink;

/**
 * Persists the OIDC server's security events so the admin console can read
 * them. Named by `oidc.audit.sink`; the package's listener resolves it per
 * event and swallows whatever this throws, so the auth flow never fails on a
 * write here.
 */
final class DatabaseAuditSink implements AuditSink
{
    /**
     * The record carries no realm — it is written from inside a realm request,
     * so the current realm is the one it belongs to. A record raised outside
     * any realm (console provisioning, key rotation) stays instance-wide.
     */
    public function record(AuditRecord $record): void
    {
        AuditEvent::create([
            'realm_id' => Realm::currentId(),
            'type' => $record->type,
            'category' => $record->category(),
            'user_id' => $record->userId,
            'client_id' => $record->clientId,
            'sid' => $record->sid,
            'ip' => $record->ip,
            'user_agent' => $record->userAgent,
            'failure' => $record->failure,
            'context' => $record->context === [] ? null : $record->context,
            'occurred_at' => $record->occurredAt,
        ]);
    }
}
