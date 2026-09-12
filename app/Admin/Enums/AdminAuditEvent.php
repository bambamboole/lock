<?php
declare(strict_types=1);

namespace App\Admin\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum AdminAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case SuperAdminGranted = 'admin.roles.granted';
    case PasswordResetSent = 'admin.password-reset.sent';
    case VerificationResent = 'admin.verification.resent';
    case UserCreated = 'admin.user.created';
    case UserUpdated = 'admin.user.updated';
    case UserBlocked = 'admin.user.blocked';
    case UserUnblocked = 'admin.user.unblocked';
    case UserDeleted = 'admin.user.deleted';
    case UserMfaReset = 'admin.user.mfa-reset';
    case UserSessionEnded = 'admin.user.session-ended';
    case UserSessionsEnded = 'admin.user.sessions-ended';
}
