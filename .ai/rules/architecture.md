---
paths:
    - "app/**"
---

# Architecture

## Organize by domain module, not technical layer

Every concern (models, enums, UI, HTTP, policies, actions) lives under `app/{Domain}/`, subdivided by technical role
only within that module — never a shared top-level technical folder (`app/Models`, `app/Http`, …) for domain code.
The existing modules are `Admin`, `Auth`, `Billing`, `Shared`, and `Tenants`.

## Multi-step writes always run inside a transaction

`DB::transaction(fn () => ...)` is the default; the manual `beginTransaction()`/`commit()`/`rollBack()` form is
acceptable when the closure doesn't fit, but a multi-step write must always be wrapped in a transaction of some form.

## Dates are immutable globally

`Date::use(CarbonImmutable::class)` is registered in `App\Shared\AppServiceProvider` — `now()` and model
date casts resolve to `CarbonImmutable`. Never use mutable `Carbon::` static calls.
