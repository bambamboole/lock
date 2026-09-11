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

`routes/api.php` runs on the `oidc` guard that `laravel-oidc` registers itself; `config/auth.php` declares no `api`
guard at all. Gate a route on scopes with `CheckScopes::using({Domain}Permission::…)` from the package.

## Behind auth:oidc, the principal is not always a person

Since laravel-oidc 0.27 a `client_credentials` token passes the `auth:oidc` guard and resolves to a
`Bambamboole\LaravelOidc\Server\Tokens\Guard\ClientPrincipal` — a client acting for itself, with no realm, no roles
and no `can()`. Reaching for any of those on it is a fatal error, not a denial, and larastan types
`$request->user()` as the app's `User`, so static analysis will not warn you.

`App\Shared\Auth\Support\RequestUser::of($request)` (and `::current()`) is the one seam that answers "is there a
person here": it returns the `User` or null. Anything that means a person — a gate check, a realm lookup, shared
Inertia props — goes through it rather than testing the principal's class at the call site.

## Middleware is attached at the route or group level

Assign middleware via `->middleware()` in `routes/*.php` or via `#[AsPage(middleware:)]`, never inside a controller.
