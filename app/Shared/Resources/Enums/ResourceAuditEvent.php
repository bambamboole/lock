<?php
declare(strict_types=1);

namespace App\Shared\Resources\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum ResourceAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case ResourceCreated = 'resource.created';
    case ResourceUpdated = 'resource.updated';
    case ResourceDeleted = 'resource.deleted';
    case ScopeCreated = 'resource.scope.created';
    case ScopeUpdated = 'resource.scope.updated';
    case ScopeDeleted = 'resource.scope.deleted';
}
