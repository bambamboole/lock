---
paths:
    - "app/Auth/**"
    - "app/Admin/**"
    - "config/auth.php"
---

# Realm-local identity

## Every user lookup carries a realm constraint

`users` is unique on `(realm_id, email)`, so one address can be two unrelated people. `config/auth.php` therefore
runs the `realm-eloquent` driver, not `eloquent`: `App\Auth\Support\RealmUserProvider` overrides `newModelQuery()` —
the one seam `retrieveById`, `retrieveByToken` and `retrieveByCredentials` all build on — and scopes it to
`Realm::currentId()`.

The constraint is inert while no realm is current, which is every guest request today. It starts protecting the
login the moment `SetCurrentRealm` derives a realm from the request instead of from the signed-in user. Do not
"simplify" the driver back to `eloquent`: without it a password valid in one realm authenticates in all of them, and
a session cookie minted in one resolves a user in another. `tests/Feature/Auth/RealmIsolationTest.php` fails on
every one of those.

## Acting on another realm's user needs runAsCurrent

Admin spans realms; credential lookup does not. Anything that resolves a user by address outside the actor's own
realm — `Password::sendResetLink()` in `App\Admin\Ui\Actions\SendUserPasswordReset` is the live case — must run
inside `$user->realm->runAsCurrent(…)`, or the broker will not find them.

## Password reset tokens are keyed by realm and email

`App\Auth\Support\RealmTokenRepository` keys `password_reset_tokens` on `(realm_id, email)`; keyed by address alone,
one realm's reset request deletes another's pending token and its token validates for the wrong user. It is wired
through `RealmPasswordBrokerManager`, rebound in `AuthServiceProvider::boot()` and **not** `register()`:
`ProviderRepository` re-adds the deferred services after the eager providers run, so a marker dropped in `register()`
is put straight back and Laravel's `PasswordResetServiceProvider` wins the rebinding.

## The realm comes from the request path, and the session cookie follows it

A realm resolved from a **host** would be isolated by the browser: separate origins, separate session cookies. From
a **path** every realm would share one cookie unless something scopes it — and the package's `ResolveRealm`
middleware does: below `/realms/{realm}` it swaps the session cookie for one named per realm (`realm.session_cookie_name`,
default `{cookie}-oidc-{slug}`) and scoped to that path, so a login in one realm is a login in that realm alone. The
application root keeps its own cookie and its `web` guard, which only the master realm's self-SSO fills. Do not
reintroduce a host assumption — the two resolutions have different security properties and are not interchangeable.

Everything that resolves a realm reads it from `Context` (`Realm::currentId()` / `Realm::fromContext()`).
`SetCurrentRealm` fills it from the `/realms/{realm}` path on package routes and from the signed-in user's own realm
everywhere else; there is no fallback realm, so `App\Auth\Actions\CreateNewUser` throws `MissingRealmContext`
when no realm is current. `App\Auth\Support\DatabaseRealmResolver` only honours the `{realm}` route parameter on the
package routes (`oidc.*`, `identity.*`), the account page (`account`) and the realm-scoped Lattice endpoints
(`realm.lattice.*`); an admin page's `{realm}` parameter is data, not the acting realm.

## A realm user's pages live below the realm, and so do their Lattice endpoints

A realm user only ever holds the realm-scoped cookie, so `App\Auth\Ui\Pages\AccountPage` is registered at
`/realms/{realm}/account` with the package's middleware order — `ResolveRealm`, then `web`, then
`AuthenticateIdentity:identity` — and `bootstrap/app.php` pins `ResolveRealm` before `StartSession` in the middleware
priority so a page may list them in any order. The root Lattice endpoints (`/lattice/forms/…`) never see that cookie,
so `routes/web.php` registers the same endpoints once more below `/realms/{realm}/lattice/…` behind the identity guard,
and `App\Realms\Support\RealmScopedLatticeEndpoints` (a `URL::formatPathUsing` hook) moves every Lattice URL minted
during a realm request under the realm. A page rendered below a realm therefore needs no special handling; a page at
the root keeps the root endpoints. `Realm::login()` sends a realm login home to that account page and a realm logout
back to the realm's own sign-in — never to `/`, which is the master realm's relying-party entry.

## laravel-oidc reads realms from the database

`App\Auth\Support\DatabaseRealmResolver` and `DatabaseRealmRepository` (bound in `AuthServiceProvider::register()`)
hand the package the `realms` row for the `/realms/{realm}` path segment, and `App\Realms\Models\Realm` implements
the package's `Realm` contract by reading its settings (`realm.*` keys, seeded from
`App\Realms\Support\RealmConfiguration::defaults()` when the realm is created). Package rows (clients, tokens,
sessions, consents) carry the realm slug in `realm_id`, so a client only exists inside the realm it was created in:
provision clients — including the first-party client — with that realm current
(`Realm::master()->runAsCurrent(...)`, as `DatabaseSeeder` does), or the package will not find them at login.

The master realm is `admin` by default (`REALM_MASTER_SLUG`), and `config('oidc.realm')` follows the same value: the
app authenticates against `APP_URL/realms/admin`, and `OIDC_RP_ISSUER` has to name that realm.

## Global roles only count inside the master realm

`User::hasGlobalPermission()` returns false for a user outside the master realm whatever roles they hold, and
`App\Admin\Ui\Actions\UpdateUserGlobalRoles` refuses such a target. Instance administrators live in the master
realm; every other realm is data to them, reachable through `User::canAccessRealm()` and the `admin:realms:manage`
permission rather than membership.
