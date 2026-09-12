<?php
declare(strict_types=1);

namespace App\Shared\Clients\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum ClientAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case ClientCreated = 'client.created';
    case ClientUpdated = 'client.updated';
    case ClientSecretRotated = 'client.secret-rotated';
    case ClientRevoked = 'client.revoked';
    case ClientRestored = 'client.restored';
    case ClientDeleted = 'client.deleted';
}
