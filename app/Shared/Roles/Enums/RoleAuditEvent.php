<?php
declare(strict_types=1);

namespace App\Shared\Roles\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum RoleAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case AssignmentsUpdated = 'role.assignments-updated';
}
