---
paths:
    - "app/*/Services/**"
    - "app/*/Data/**"
---

# Services and Data

## A class that carries domain logic behind an injectable is a service

It belongs in `app/{Domain}/Services/`, resolved from the container by type hint. No `Service` suffix is required —
name it for what it does.

`Support/` stays for the smaller helpers that are not services and not data: pure functions over their arguments,
static registries, string manglers, and thin adapters a config entry names by class-string rather than the container
injecting. `App\Tenants\Support\Audit`, `App\Tenants\Support\TenantMemberRoles`, and `App\Auth\Support\OidcScopeCatalog`
(wired through `config('oidc.scopes')`) are the shape that belongs there. The permission plumbing
(`PermissionRepository`, `PermissionDefinition`) also lives under `Support/` — a one-class `Services/` or `Data/` directory next to `Support/` is not worth the extra slice.

## Value objects that carry data between layers live in Data/

`app/{Domain}/Data/` holds the immutable value objects a service returns and the UI consumes — `readonly` classes
with typed, promoted properties, no Eloquent, no container.

## Both slices are checked by the architecture test

`tests/Feature/ArchitectureTest.php` derives its namespace list from the directories that exist, but only for the
slice names in its `$slices` brace list. Adding a new technical slice under `app/{Domain}/` means adding it there
too, or it silently escapes the "must not import Lattice" rules.
