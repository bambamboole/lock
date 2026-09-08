<?php
declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\Concerns\IsAuditEvent;

/**
 * A typed audit-log event. Implemented by per-domain string-backed enums via
 * {@see IsAuditEvent}; the backing value is the stable `event` string
 * persisted on the activity_log row.
 */
interface AuditEvent
{
    public function eventName(): string;

    public function translationKey(): string;
}
