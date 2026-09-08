---
paths:
    - "app/**"
---

# Architecture

## Organize by domain module, not technical layer

Every concern (models, enums, UI, HTTP, policies, actions) lives under `app/{Domain}/`, subdivided by technical role
only within that module — never a shared top-level technical folder (`app/Models`, `app/Http`, …) for domain code.
The existing modules are `Admin`, `Auth`, `Shared`, and `Realms`.

## A domain reaches another domain only through app/Shared/{Domain}

`app/Shared/{Domain}/` is the public surface of a domain: the contracts, attributes, traits, and DTOs other modules
may depend on (`app/Shared/Auth/Contracts/PermissionEnum`, `app/Shared/Auth/Attributes/AsPermission`,
`app/Shared/Audit/Contracts/AuditEvent`). It imports nothing from `app/{Domain}/`; the domain provider binds each
contract to its implementation. `App\Auth\Models\User` and `App\Realms\Models\Realm` are the shared kernel and
may be imported anywhere — relations, route bindings, and policies need the concrete class. Everything else crossing a
domain line is a violation. `tests/Feature/ArchitectureTest.php` enforces this; the classes still listed in its
`architectureDomainBoundaryBaseline()` are the remaining debt, and that list only shrinks.

## Multi-step writes always run inside a transaction

`DB::transaction(fn () => ...)` is the default; the manual `beginTransaction()`/`commit()`/`rollBack()` form is
acceptable when the closure doesn't fit, but a multi-step write must always be wrapped in a transaction of some form.

## Dates are immutable globally

`Date::use(CarbonImmutable::class)` is registered in `App\Shared\AppServiceProvider` — `now()` and model
date casts resolve to `CarbonImmutable`. Never use mutable `Carbon::` static calls.
