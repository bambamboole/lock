<?php
declare(strict_types=1);

namespace App\Realms\Enums;

use App\Shared\Audit\Concerns\IsAuditEvent;
use App\Shared\Audit\Contracts\AuditEvent;

enum RealmAuditEvent: string implements AuditEvent
{
    use IsAuditEvent;

    case RealmUpdated = 'realm.updated';
    case InvitationCreated = 'invitation.created';
    case InvitationResent = 'invitation.resent';
    case InvitationCancelled = 'invitation.cancelled';
    case MemberRoleChanged = 'member.role-changed';
    case MemberRemoved = 'member.removed';
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
}
