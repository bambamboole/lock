<?php
declare(strict_types=1);

namespace App\Realms\Enums;

use App\Auth\Attributes\AsPermission;
use App\Auth\Attributes\AsScope;
use App\Auth\Concerns\HasPermissionLabel;
use App\Auth\Contracts\PermissionEnum;

#[AsPermission('navigation.realm-settings')]
enum RealmPermission: string implements PermissionEnum
{
    use HasPermissionLabel;

    /*
     * #[AsScope]-tagged cases become OAuth scopes in the OIDC scope catalog and
     * are what a relying party may request on the consent screen. Global
     * permissions never become scopes.
     */
    case UpdateRealm = 'realm:update';

    case DeleteRealm = 'realm:delete';

    #[AsScope]
    case ViewMembers = 'member:view';

    #[AsScope]
    case ManageMembers = 'member:manage';

    case ViewAudit = 'audit:view';

    case ManageRoles = 'role:manage';
}
