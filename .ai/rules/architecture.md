---
paths:
    - "app/**"
---

# Architecture

## Organize by domain module, not technical layer

Every concern (models, enums, UI, HTTP, policies, actions) lives under `app/{Domain}/`, subdivided by technical role
only within that module — never a shared top-level technical folder (`app/Models`, `app/Http`, …) for domain code.
The existing modules are `Admin`, `Audit`, `Auth`, `Clients`, `Realms`, `Resources`, `Roles`, and `Shared`.

## A domain reaches another domain only through app/Shared/{Domain}

`app/Shared/{Domain}/` is the public surface of a domain: the contracts, attributes, traits, and DTOs other modules
may depend on (`app/Shared/Auth/Contracts/PermissionEnum`,
`app/Shared/Audit/Contracts/AdminEventType`). A concern no single domain owns — the API's own OAuth surface, the
scope vocabulary — is a whole module there (`app/Shared/Api`, `app/Shared/Authorization`). A seam is only the seam:
`app/Shared/Audit` holds `Audit::record()`, the event it raises and the type contract, while the models, the
listener, the sink and the UI are `app/Audit`'s. It imports nothing from `app/{Domain}/`; the domain provider binds each
contract to its implementation. `App\Auth\Models\User`, `App\Realms\Models\Realm`, `App\Roles\Models\Role`,
`App\Resources\Models\Resource` and `App\Resources\Models\ResourceScope` are the shared kernel and may be imported anywhere — relations, route bindings, and policies need the concrete class. Behaviour a neighbour
needs is a contract there (`App\Shared\Auth\Contracts\DeletesUser`, `App\Shared\Realms\Contracts\CreatesRealm`),
bound to its implementation in the owning domain's provider. Everything else crossing a
domain line is a violation, and `tests/Feature/ArchitectureTest.php` fails on it.

## Multi-step writes always run inside a transaction

`DB::transaction(fn () => ...)` is the default; the manual `beginTransaction()`/`commit()`/`rollBack()` form is
acceptable when the closure doesn't fit, but a multi-step write must always be wrapped in a transaction of some form.

## Dates are immutable globally

`Date::use(CarbonImmutable::class)` is registered in `App\Shared\AppServiceProvider` — `now()` and model
date casts resolve to `CarbonImmutable`. Never use mutable `Carbon::` static calls.
