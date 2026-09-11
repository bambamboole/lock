---
paths:
    - "app/Auth/**"
    - "app/Admin/**"
    - "app/Realms/**"
    - "config/auth.php"
---

# Realm-local identity

## Every user lookup carries a realm constraint

`users` is unique on `(realm_id, email)`, so one address can be two unrelated people. `config/auth.php` therefore
runs the `realm-eloquent` driver, not `eloquent`: `App\Auth\Support\RealmUserProvider` overrides `newModelQuery()` —
the one seam `retrieveById`, `retrieveByToken` and `retrieveByCredentials` all build on — and scopes it to
`Realm::currentId()`.

The constraint is inert while no realm is current. On every route a realm serves `SetCurrentRealm` derives the realm
from the request host, so it protects every login, session and token lookup there. Do not
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

## The realm comes from the request host

`config('oidc.routes.realms')` is `domain`: every realm is served from a host of its own, the master realm from the
host in `APP_URL` (never stored, `Realm::masterHost()`), every other realm from `realms.domain`. The browser isolates
the realms by origin, so there is no per-realm session cookie any more; `SESSION_DOMAIN` has to stay empty, or realms
on subdomains of one parent would share a cookie. Do not bring back a `/realms/{slug}` path or a cookie-name setting.

On the routes a realm serves — `App\Shared\Realms\Support\RealmRoutes::SERVED`: `oidc.*`, `identity.*`, `account`
and `realm.lattice.*` — the host decides the realm (`App\Auth\Support\DatabaseRealmResolver`). Everywhere else the
current realm comes from `Context` (`Realm::currentId()` / `Realm::fromContext()`): `SetCurrentRealm` fills it from
the host on those routes and from the signed-in user's own realm everywhere else. It also runs on the protocol
endpoints outside the `web` group (`oidc.routes.middleware`), so the realm constraint on user lookup holds for
`userinfo` too. There is no fallback realm for writes: `App\Auth\Actions\CreateNewUser` throws `MissingRealmContext`
when no realm is current. An admin page's `{realm}` parameter is data, not the acting realm.

`App\Realms\Support\RealmHostGate` runs on `RouteMatched`: a host no realm is served from gets a 404 on everything
but `/up` and the domain check (which keeps a forged Host header out of every URL the app would mint from it), the
master realm's host serves everything, and any other realm's host serves only `RealmRoutes::SERVED` plus the
translations — its root redirects to the account page. The gate also points the WebAuthn relying party at the host,
because a passkey is bound to the host it was created on.

## URLs for a realm's pages carry that realm's host

The issuer is `Realm::origin()` wherever a token is minted — `App\Auth\Support\RealmIssuerResolver` replaces the
package's, which falls back to `APP_URL` outside a request and would stamp the master realm on a logout token the
scheduler signs for another realm. `Realm::makeCurrent()` keeps the realm's origin in hidden `Context` beside its key,
and a `URL::formatHostUsing` hook in `RealmsServiceProvider` gives every absolute URL to a served route that origin —
so a password reset an administrator sends, or a mail a queued job renders, links to the recipient's realm. The hook
cannot see `url()`/`URL::to()`, which is why `AuthServiceProvider` replaces the package's `ResetPassword` and
`VerifyEmail` URL builders with named routes minted inside the recipient's realm. Mint realm links through `route()`,
never `url()`.

## A realm user's pages live on the realm host, and so do their Lattice endpoints

`App\Auth\Ui\Pages\AccountPage` is registered at `/account` with the package's middleware order — `ResolveRealm`,
then `web`, then `AuthenticateIdentity:identity` — and `bootstrap/app.php` pins `ResolveRealm` before `StartSession`
in the middleware priority so a page may list them in any order. The root Lattice endpoints (`/lattice/forms/…`)
authenticate with the console's `web` guard, which on the master realm's host shares the session with the identity
guard, so `routes/web.php` registers the same endpoints once more below `/account/lattice/…` behind the identity guard,
and `App\Realms\Support\RealmScopedLatticeEndpoints` (a `URL::formatPathUsing` hook) moves every Lattice URL minted
during a realm request there. `Realm::login()` sends a realm login home to that account page and a realm logout back
to the realm's own sign-in — never to `/` on the master host, which is its relying-party entry.

## A realm's domain is checked, never trusted

`App\Realms\Actions\CheckRealmDomain` asks `https://{domain}/.well-known/lock/domain-check` for an HMAC of the host
under `APP_KEY` (`App\Realms\Support\DomainCheckToken`) and records `RealmDomainStatus` on the realm: DNS, the
platform proxy and TLS all have to lead to this instance, redirects are not followed. The console runs it after
creating a realm, after changing its domain and on request; an unverified realm is still created and served. The
check is an outbound request to an administrator-typed host, so `App\Shared\Realms\Support\RealmDomain` only admits
lowercase host names — no IP literals, ports or paths.

## laravel-oidc reads realms from the database

`App\Auth\Support\DatabaseRealmResolver` and `DatabaseRealmRepository` (bound in `AuthServiceProvider::register()`)
hand the package the `realms` row for the request host (`findByDomain()`), and `App\Realms\Models\Realm` implements
the package's `Realm` contract by reading its settings (`realm.*` keys, seeded from
`App\Shared\Realms\Support\RealmConfiguration::defaults()` when the realm is created). Package rows (clients, tokens,
sessions, consents) carry the realm slug in `realm_id`, so a client only exists inside the realm it was created in:
provision clients — including the first-party client — with that realm current
(`Realm::master()->runAsCurrent(...)`, as `DatabaseSeeder` does), or the package will not find them at login.

The master realm is `admin` by default (`REALM_MASTER_SLUG`), and `config('oidc.realm')` follows the same value: the
app authenticates against `APP_URL` itself, and `OIDC_RP_ISSUER` is `APP_URL`.

## Global roles only count inside the master realm

`User::hasGlobalPermission()` returns false for a user outside the master realm whatever roles they hold, and
`App\Admin\Ui\Actions\UpdateUserGlobalRoles` refuses such a target. Instance administrators live in the master
realm; every other realm is data to them, reachable through `User::canAccessRealm()` and the `admin:realms:manage`
permission rather than membership.
