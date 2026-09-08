# Lock

An OpenID Connect identity provider built on Laravel — a Keycloak-style IdP with a server-driven
admin console rendered through [Lattice](https://latticephp.com). Pages, forms, tables, and layouts
are defined in PHP and rendered as React components over [Inertia](https://inertiajs.com).

## Features

- **OpenID Connect provider** — authorization code + PKCE, token exchange, consent, discovery, and
  key rotation via [`laravel-oidc`](https://github.com/bambamboole/laravel-oidc), backed by
  [Laravel Passport](https://laravel.com/docs/passport) for OAuth client and token storage.
- **Relying-party login** — the app signs its own users in through its own OIDC endpoints
  (self-SSO), so the account console runs on the same flows external clients use.
- **Authentication** — registration, password reset, email verification, **two-factor** (TOTP,
  recovery codes) and **passkeys**, with the prebuilt Lattice auth UI from `laravel-oidc-ui`.
- **Tenants** — multi-tenant workspaces with memberships, roles, permissions, and email invitations.
  These are the basis for realm-scoped identity.
- **Admin console** — global roles, user and tenant administration, and a cross-tenant activity view.
- **Audit log** — a tenant-scoped activity trail via
  [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog).
- **Realtime** — live in-app notifications via [Laravel Reverb](https://laravel.com/docs/reverb).
- **i18n** — English and German out of the box with enforced key parity.
- **Modern frontend** — React 19, Inertia 3, Tailwind CSS v4, TypeScript.

## Requirements

- PHP 8.4+, Composer, Node.js 22+
- [Laravel Herd](https://herd.laravel.com)

## Getting started

```bash
composer setup
```

That installs dependencies, writes `.env`, generates app and Passport keys, runs the migrations,
rotates the OIDC signing keys, and builds the frontend. Then start everything (app server, queue,
Reverb, logs, Vite):

```bash
composer dev
```

A demo user is seeded for local development: **test@example.com** / **password**. In the `local`
environment the login form ships prefilled with them, so signing in is a single click.

## Testing

```bash
composer ci:check        # lint/format/type checks, then Pint + the Pest suite
vendor/bin/phpstan analyse
```

## License

MIT.
