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

`App\Shared\Audit\Models\Activity` (activitylog) extends a package model and inherits its primary key and table.
Do not add `HasUuids` or `#[Fillable]` to it — configure the package instead.

laravel-oidc is not extensible this way: it hardcodes its own `Token` and `Client` classes everywhere it writes, so
a subclass would never be the hydrated model. Read its rows through the package classes.

## Realm-owned models extend BusinessModel; scoping is automatic

A model that belongs to a realm extends `App\Shared\Models\BusinessModel` — it bundles `BelongsToRealm` (strict `RealmScope` global scope plus a `realm_id` backfill on create), `HasUuids`, `RecordsActivity`, and `SoftDeletes`. Never hand-write `where('realm_id', …)` on such a model.

`RealmScope` is strict: a query with no `realm_id` in `Context` throws `MissingRealmContext` instead of quietly reading every realm. Make a realm current with `$realm->makeCurrent()` (request paths) or `$realm->runAsCurrent(fn () => …)` (schedulers, jobs, maintenance scripts — it restores the previous context). A deliberate cross-realm read is `withoutGlobalScope(RealmScope::class)`, spelled out at the call site. `bootstrap/app.php` renders the exception as a 403 on `api/*`.

A model that must also show shared/global rows (`realm_id` null) carries **both** `#[ScopedBy(GlobalOrRealmScope::class)]` and the `BelongsToRealmOrGlobal` trait — `#[ScopedBy]` adds a scope, it does not replace the strict one, and the trait is what neutralises it. Creation stays realm-bound either way: a shared row is never implicit, it needs the explicit `->asGlobal()` switch.

The identity models (Realm, User, Role) deliberately stay unscoped: the admin console reads them across realms. `Realm` hard-deletes — a deleted realm frees its slug — and `App\Realms\Actions\DeleteRealm` is the only place that removes one, because the package rows keyed by the realm slug have no foreign key to cascade from.
