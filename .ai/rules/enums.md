---
paths:
    - "app/**/Enums/*Permission.php"
    - "app/*/Policies/**"
    - "app/Auth/Support/PermissionRepository.php"
---

# Enums

## Permissions are discovered from #[AsPermission] enums, never synced

Every domain owns a string-backed permission enum tagged `#[AsPermission('group.label')]` (`global: true` for admin-style enums) implementing `App\Shared\Auth\Contracts\PermissionEnum` via the `HasPermissionLabel` trait. `App\Auth\Support\PermissionRepository` (singleton, read outside Auth through the `App\Shared\Auth\Contracts\PermissionCatalog` contract) discovers every tagged enum under `app/` and caches the catalog forever — adding a permission is one enum case plus its `lang/{en,de}/{domain}.php` description under `{domain}.permissions.{value with : -> .}`. `php artisan permission:sync` rebuilds the forever cache and creates/re-syncs the protected global role grants from `App\Admin\Support\GlobalRoles`; `php artisan app:deploy` runs it on every deploy.

A permission is never an OAuth scope. OAuth scopes are rows: each one belongs to a resource of a realm
(`App\Resources\Models\ResourceScope`), and `App\Resources\Support\ResourceScopeCatalog` serves the scopes of the
requested RFC 8707 resource to laravel-oidc. The same scope value under two resources is two different scopes.

Check permissions with the enum case, never a string: `$user->can(AdminPermission::ManageRealms)` in code, `can: [AdminPermission::Access, AdminPermission::ManageUsers]` in `#[AsPage]`/`#[AsTable]`/`#[AsAction]`/`#[AsFragment]`. Global checks resolve through the single `Gate::before` in `AuthServiceProvider`, which only grants users of the master realm. A granted `{prefix}:manage` satisfies a `{prefix}:view` check (`User::hasGlobalPermission()`), so read permissions must end in `:view`. Grants persist as `permissions.key` rows joined via `permission_role`, created lazily by `Role::syncPermissions()`.
