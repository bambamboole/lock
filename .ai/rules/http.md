---
paths:
    - "app/*/Http/**"
---

# Http

## Split each domain's Http/ by purpose, and version the API

`Http/Controllers` for web controllers, `Http/Requests` for their Form Requests, `Http/Middleware` for middleware.
A token-authenticated JSON endpoint — one gated in `routes/api.php` by `CheckScopes::using(ApiScope::…)` — goes under
`Http/Api/V1/Controllers`, with its `JsonResource` classes in `Http/Api/V1/Resources` and its Form Requests in
`Http/Api/V1/Requests`. Never put an API controller or resource directly under `Http/Controllers` or
`Http/Resources`: the version segment is what makes a breaking API change a new folder instead of an edit to the one
every client already calls.

A domain never reaches into another domain's `Http/`.

## API endpoints authenticate with auth:oidc

`routes/api.php` runs on the `oidc` guard that `laravel-oidc` registers itself; `config/auth.php` declares no `api`
guard at all. Gate a route on scopes with the package's `CheckScopes::using(ApiScope::…->value)` — never an
`AdminPermission`: a permission is a grant on a person, a scope is a claim in a token (see `.ai/rules/enums.md`).

The API is the master realm's own protected resource. `App\Shared\Api` owns it: `ApiScope` is the scope catalog,
`ManagementApi` reconciles the resource row and its scopes (from `app:bootstrap`, so every deploy restores them) and
answers which rows the console must refuse to touch. `EnsureManagementApiAudience` narrows every token to that
resource, because the guard only checks a token is addressed to _one_ of the realm's audiences. The scopes each
operation requires reach the OpenAPI document through `DocumentsTokenAuthorization`, which reads them off the route's
`CheckScopes` middleware and replaces Scramble's inferred `AuthenticationException` with the RFC 6750 error body the
guard really returns. The reference's playground mints a token per operation scope set from exactly those scopes, so
an operation whose security requirement is missing cannot be executed there.

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
