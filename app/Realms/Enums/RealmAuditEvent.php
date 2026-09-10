<?php
declare(strict_types=1);

namespace App\Realms\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum RealmAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case RealmCreated = 'realm.created';
    case RealmUpdated = 'realm.updated';
    case RealmDeleted = 'realm.deleted';
    case ClientCreated = 'realm.client.created';
    case ClientUpdated = 'realm.client.updated';
    case ClientSecretRotated = 'realm.client.secret-rotated';
    case ClientRevoked = 'realm.client.revoked';
    case ClientRestored = 'realm.client.restored';
    case ClientDeleted = 'realm.client.deleted';
}
