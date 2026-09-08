---
paths:
    - "app/*/Models/**"
    - "app/Shared/Settings/Models/**"
---

# Models

## UUID primary keys on every domain model

Every domain model uses `HasUuids`; never rely on auto-increment integer ids for domain tables.
`tests/Feature/ArchitectureTest.php` asserts this, but against a hardcoded model list — add your new model to that
list, otherwise the rule goes unchecked for it.

## Mass assignment via the #[Fillable] attribute

Declare mass-assignable attributes with the `#[Fillable([...])]` class attribute, never the legacy `$fillable` /
`$guarded` properties.

## Models extending a vendor model keep the vendor's key strategy

`App\Auth\Models\OAuthAccessToken` (Passport) and `App\Tenants\Models\Activity` (activitylog) extend a package model
and inherit its primary key and table. Do not add `HasUuids` or `#[Fillable]` to those — configure the package
instead.

## Tenant-owned models extend BusinessModel; scoping is automatic

A model that belongs to a tenant extends `App\Shared\Models\BusinessModel` — it bundles `BelongsToTenant` (strict `TenantScope` global scope plus a `tenant_id` backfill on create), `HasUuids`, `RecordsActivity`, and `SoftDeletes`. Never hand-write `where('tenant_id', …)` on such a model.

`TenantScope` is strict: a query with no `tenant_id` in `Context` throws `MissingTenantContext` instead of quietly reading every tenant. Make a tenant current with `$tenant->makeCurrent()` (request paths) or `$tenant->runAsCurrent(fn () => …)` (schedulers, jobs, maintenance scripts — it restores the previous context). A deliberate cross-tenant read is `withoutGlobalScope(TenantScope::class)`, spelled out at the call site. `bootstrap/app.php` renders the exception as a 403 on `api/*`.

A model that must also show shared/global rows (`tenant_id` null) carries **both** `#[ScopedBy(GlobalOrTenantScope::class)]` and the `BelongsToTenantOrGlobal` trait — `#[ScopedBy]` adds a scope, it does not replace the strict one, and the trait is what neutralises it. Creation stays tenant-bound either way: a shared row is never implicit, it needs the explicit `->asGlobal()` switch.

The tenant infrastructure models (Tenant, Role, TenantInvitation, Membership) deliberately stay unscoped: they are queried from admin and invitation-acceptance paths that have no current tenant.
