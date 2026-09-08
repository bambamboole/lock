<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum AdminAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case GlobalRolesUpdated = 'admin.roles.updated';
    case SuperAdminGranted = 'admin.roles.granted';
    case PasswordResetSent = 'admin.password-reset.sent';
    case VerificationResent = 'admin.verification.resent';
}
