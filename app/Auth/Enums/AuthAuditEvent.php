<?php
declare(strict_types=1);

namespace App\Auth\Enums;

use App\Shared\Concerns\IsAuditEvent;
use App\Shared\Contracts\AuditEvent;

enum AuthAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case ApiTokenCreated = 'api-token.created';
    case ApiTokenRevoked = 'api-token.revoked';
}
