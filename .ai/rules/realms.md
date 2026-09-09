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

## laravel-oidc's RealmResolver is deliberately unbound

Leaving it unbound keeps every package row in the configured `oidc.realm` (`default`). Binding it to the app's
current realm looks right and breaks login: the app provisions exactly one first-party client, `oidc:install-self`
creates it with no realm current, and the package's `ClientRepository::create()` never writes `realm_id` at all — it
relies on the column default. A `master`-realm user then authorizes against a `default`-realm client and
`findActive()` returns null. Bind it only once clients are provisioned per realm.
