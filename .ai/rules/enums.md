---
paths:
    - "app/**/Enums/*Permission.php"
    - "app/*/Policies/**"
    - "app/Auth/Support/PermissionRepository.php"
---

# Enums

## Permissions are discovered from #[AsPermission] enums, never synced

Every domain owns a string-backed permission enum tagged `#[AsPermission('group.label')]` (`global: true` for admin-style enums) implementing `App\Shared\Auth\Contracts\PermissionEnum` via the `HasPermissionLabel` trait. `App\Auth\Support\PermissionRepository` (singleton) discovers every tagged enum under `app/` and caches the catalog forever — adding a permission is one enum case plus its `lang/{en,de}/{domain}.php` description under `{domain}.permissions.{value with : -> .}`. `php artisan permission:sync` rebuilds the forever cache and creates/re-syncs the protected global role grants from `App\Admin\Support\GlobalRoles`; `php artisan app:deploy` runs it on every deploy.

Tag a realm case with `#[AsScope]` (a pure marker) to expose it in the OIDC scope catalog; global permissions never become API scopes and tagging one fails discovery.

Check permissions with the enum case, never a string: `$user->can(RealmPermission::ViewAudit, $realm)` in code, `can: [AdminPermission::Access, AdminPermission::ManageUsers]` in `#[AsPage]`/`#[AsTable]`/`#[AsAction]`/`#[AsFragment]`. Realm-scoped checks resolve through `RealmPolicy::__call` (never hand-write a one-line policy bridge); global ones through the single `Gate::before` in `AuthServiceProvider`. A granted `{prefix}:manage` satisfies a `{prefix}:view` check, so read permissions must end in `:view`. Grants persist as `permissions.key` rows joined via `permission_role`, created lazily by `Role::syncPermissions()`.
