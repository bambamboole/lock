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

## Realm-owned models are reached through their realm

The console reads a realm's rows while the administrator's own realm is current, so realm-owned models carry no
global scope. `RealmRole`, `App\Resources\Models\Resource` and its `ResourceScope` are plain models reached through
`$realm->realmRoles()` and `$realm->realmResources()`, never a hand-written `realm_id` constraint. `Resource` and
`ResourceScope` are listed in `architectureSharedKernel()` because the admin console binds a `Resource` by route and
names it in contracts. `Realm::resources()` is the package's settings
contract, not the relation; the relation has to be called something else.

`resource` is a soft-reserved PHP type keyword, so Pint's `phpdoc_types` fixer lowercases a bare `Resource` in any
docblock and PHPStan then rejects the case. Write the class fully qualified (`\App\Resources\Models\Resource`) in
generics, `@property` tags and `@extends` clauses.

`Realm` hard-deletes — a deleted realm frees its slug — and `App\Realms\Actions\DeleteRealm` is the only place that removes one, because the package rows keyed by the realm slug have no foreign key to cascade from.
