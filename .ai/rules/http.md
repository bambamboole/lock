---
paths:
    - "app/*/Http/**"
---

# Http

## Split each domain's Http/ by purpose, and version the API

`Http/Controllers` for web controllers, `Http/Requests` for their Form Requests, `Http/Middleware` for middleware.
A token-authenticated JSON endpoint — one gated in `routes/api.php` by `CheckToken::using({Domain}Permission::…)` —
goes under `Http/Api/V1/Controllers`, with its `JsonResource` classes in `Http/Api/V1/Resources`. Never put an API
controller or resource directly under `Http/Controllers` or `Http/Resources`: the version segment is what makes a
breaking API change a new folder instead of an edit to the one every client already calls.

A domain never reaches into another domain's `Http/`.

## API endpoints authenticate with auth:oidc

`routes/api.php` runs on the `oidc` guard that `laravel-oidc` registers itself, not Passport's `auth:api`. The
`api` guard in `config/auth.php` is never routed to but must stay — Passport resolves the personal-access-token
provider by looking for a guard with the `passport` driver.

## Middleware is attached at the route or group level

Assign middleware via `->middleware()` in `routes/*.php` or via `#[AsPage(middleware:)]`, never inside a controller.
