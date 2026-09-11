# Lock

An OpenID Connect identity provider built on Laravel — a Keycloak-style IdP with a server-driven
admin console rendered through [Lattice](https://latticephp.com). Pages, forms, tables, and layouts
are defined in PHP and rendered as React components over [Inertia](https://inertiajs.com).

## Features

- **OpenID Connect provider** — authorization code + PKCE, token exchange, consent, discovery, and
  key rotation via [`laravel-oidc`](https://github.com/bambamboole/laravel-oidc), which owns the OAuth2 core
  and its client and token storage.
- **Relying-party login** — the app signs its own users in through its own OIDC endpoints
  (self-SSO), so the account console runs on the same flows external clients use.
- **Authentication** — registration, password reset, email verification, **two-factor** (TOTP,
  recovery codes) and **passkeys**, with the prebuilt Lattice auth UI from `laravel-oidc-ui`.
- **Realms** — the identity boundary, Keycloak-style. A user belongs to exactly one realm, an address identifies
  a different person in each, and every realm has its own OIDC issuer, clients and authentication settings.
- **Admin console** — the `admin` realm's first-party UI: realm CRUD and configuration, user administration,
  global roles, and a cross-realm activity view.
- **Audit log** — a tenant-scoped activity trail via
  [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog).
- **i18n** — English and German out of the box with enforced key parity.
- **Modern frontend** — React 19, Inertia 3, Tailwind CSS v4, TypeScript.

## Requirements

- PHP 8.4+, Composer, Node.js 22+
- [Laravel Herd](https://herd.laravel.com)

## Getting started

```bash
composer setup
```

That installs dependencies, writes `.env`, generates the app key, runs the migrations,
rotates the OIDC signing keys, provisions the console client and the first administrator from
the `LOCK_*` values in `.env`, and builds the frontend. Then start everything (queue, logs, Vite —
Herd serves the app):

```bash
composer dev
```

The local administrator is **test@example.com** / **password**. In the `local`
environment the login form ships prefilled with them, so signing in is a single click.

## Testing

```bash
composer ci:check    # lint, format and type checks, PHPStan, Rector, then Pint + the Pest suite
```

## License

MIT.
