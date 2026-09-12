---
paths:
    - "app/**/Enums/ApiScope.php"
    - "app/*/Policies/**"
---

# Enums

## Scopes are the only authorization currency

There are no permissions. A grant is an OAuth scope, and every scope is a row: it belongs to a resource of a realm
(`App\Resources\Models\ResourceScope`), and `App\Resources\Support\ResourceScopeCatalog` serves the scopes of the
requested RFC 8707 resource to laravel-oidc. The same scope value under two resources is two different scopes.

`App\Shared\Api\Enums\ApiScope` is the catalog of the management API's resource — the one the console gates on.
`ManagementApi` reconciles those rows on `app:bootstrap`, so adding a case reaches the database on the next deploy,
and `App\Shared\Api\ManagementRoles` maps the protected `Super Admin`/`Support` roles to their cases.

A role is a container for scope rows: `RealmRole::scopes()` over `realm_role_scope`, always scopes of the role's own
realm. Because the management API's resource exists only on the master realm, a role outside it cannot reach the
console, and there is no master-realm special case to write.

## One subset rule, read the same on both sides

Check scopes with the enum case, never a string: `$user->can(ApiScope::RealmsWrite)` in code,
`can: ApiScope::RealmsWrite` in `#[AsPage]`/`#[AsTable]`/`#[AsAction]`/`#[AsFragment]`. They resolve through the
single `Gate::before` in `AdminServiceProvider`, which reads `User::hasManagementScope()`.

Every listed scope is required — the same rule `CheckScopes` applies to a token. Nothing is implied: `realms:write`
does not grant `realms:read`, so a role that may write lists both. A scope value is `{area}:read` or `{area}:write`,
and `tests/Feature/ArchitectureTest.php` fails on anything else.
