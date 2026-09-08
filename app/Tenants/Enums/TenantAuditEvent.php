<?php
declare(strict_types=1);

namespace App\Tenants\Enums;

use App\Shared\Concerns\IsAuditEvent;
use App\Shared\Contracts\AuditEvent;

enum TenantAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case TenantUpdated = 'tenant.updated';
    case InvitationCreated = 'invitation.created';
    case InvitationResent = 'invitation.resent';
    case InvitationCancelled = 'invitation.cancelled';
    case MemberRoleChanged = 'member.role-changed';
    case MemberRemoved = 'member.removed';
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
}
