<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum AdminAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case SuperAdminGranted = 'admin.roles.granted';
}
