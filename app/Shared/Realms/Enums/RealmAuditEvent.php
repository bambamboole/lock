<?php
declare(strict_types=1);

namespace App\Shared\Realms\Enums;

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
    case SocialProviderCreated = 'realm.social-provider.created';
    case SocialProviderUpdated = 'realm.social-provider.updated';
    case SocialProviderDeleted = 'realm.social-provider.deleted';
    case RoleCreated = 'realm.role.created';
    case RoleUpdated = 'realm.role.updated';
    case RoleDeleted = 'realm.role.deleted';
    case UserRolesUpdated = 'realm.user.roles-updated';
}
