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

## The realm comes from the request path, and the session does not follow it

A realm resolved from a **host** would be isolated by the browser: separate origins, separate session cookies. From
a **path** every realm shares one cookie, so a login in one realm is a login in all of them unless `session.path` is
scoped per realm. Nothing does that yet — `config/session.php` sets no `path`, so the framework default `/` applies.
Scoping it is the first thing that has to land alongside the path segment, not after it.

Everything that resolves a realm reads it from `Context` (`Realm::currentId()` / `Realm::fromContext()`), and falls
back to `realm.default_slug` for a request that names none. `App\Auth\Actions\CreateNewUser` is the pattern: it
registers into the current realm and into the default only while requests carry no realm. Do not reintroduce a host
assumption — three comments carried one and were corrected; the two resolutions have different security properties
and are not interchangeable.

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
