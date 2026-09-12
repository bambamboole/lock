---
paths:
    - "app/**/Enums/*Permission.php"
    - "app/*/Policies/**"
---

# Enums

## AdminPermission is the permission catalog

Every permission is a case of `App\Shared\Admin\Enums\AdminPermission`, a string-backed enum implementing the
`App\Shared\Auth\Contracts\PermissionEnum` marker. It lives in `App\Shared` because every domain contributing a
console page gates on it; `AdminServiceProvider` owns its `Gate::before` binding. Adding a permission is one enum case. `App\Admin\Support\GlobalRoles` maps the protected `Super
Admin`/`Support` roles to their cases, and `php artisan permission:sync` (run by `app:deploy`) re-syncs those grants
so a new case reaches the Super Admin role on the next deploy.

A permission is never an OAuth scope. OAuth scopes are rows: each one belongs to a resource of a realm
(`App\Resources\Models\ResourceScope`), and `App\Resources\Support\ResourceScopeCatalog` serves the scopes of the
requested RFC 8707 resource to laravel-oidc. The same scope value under two resources is two different scopes.

Check permissions with the enum case, never a string: `$user->can(AdminPermission::ManageRealms)` in code,
`can: [AdminPermission::Access, AdminPermission::ManageUsers]` in `#[AsPage]`/`#[AsTable]`/`#[AsAction]`/`#[AsFragment]`.
They resolve through the single `Gate::before` in `AdminServiceProvider`, which only grants users of the master realm.
A granted `{prefix}:manage` satisfies a `{prefix}:view` check (`User::hasGlobalPermission()`), so read permissions must
end in `:view`. Grants persist as `permissions.key` rows joined via `permission_role`, created lazily by
`Role::syncPermissions()`, which also prunes keys the enum no longer declares.
