# SaaS Starter Kit

A batteries-included, multi-tenant SaaS starter built on Laravel with server-driven React via
[Lattice](https://latticephp.com). Pages, forms, tables, and layouts are defined in PHP and
rendered as React components over [Inertia](https://inertiajs.com).

## Features

- **Server-driven UI** — pages, forms, tables, and layouts declared in PHP with Lattice.
- **Authentication** — OpenID Connect provider and relying-party login, registration, password reset,
  email verification, **two-factor**, and **passkeys** via `laravel-oidc`, with the prebuilt
  Lattice auth UI from its `laravel-oidc-ui` package.
- **Tenants/teams** — multi-tenant workspaces with memberships, roles, and email invitations.
- **OAuth API** — tenant-bound personal access tokens with role-projected scopes via
  [Laravel Passport](https://laravel.com/docs/passport), plus per-plan token-bucket rate limiting.
- **Billing** — subscriptions, trials, and a subscription gate via
  [Cashier Paddle](https://laravel.com/docs/cashier-paddle).
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
composer create-project bambamboole/saas-starter-kit my-app
cd my-app
```

`composer create-project` runs `php artisan app:install`, which prompts for your application name
and dev domain and rebrands the project (`.env`, `composer.json`, README, welcome copy, `solo.yml`,
and the agent docs). To rerun or script it:

```bash
php artisan app:install --name="Acme" --domain=acme.test
```

Then start everything (app server, queue, Reverb, logs, Vite):

```bash
composer dev
```

A demo user is seeded for local development: **test@example.com** / **password**. In the `local`
environment the login form ships prefilled with them, so signing in is a single click.

## Billing setup

Billing uses Paddle. `.env.example` ships placeholder catalog IDs — create your products and prices
in the [Paddle dashboard](https://www.paddle.com) and set `PADDLE_*` in `.env`
(`PADDLE_CLIENT_SIDE_TOKEN`, `PADDLE_API_KEY`, `PADDLE_WEBHOOK_SECRET`, and the product/price IDs).
Plan pricing and features live in `config/billing.php`. The billing browser test only requires the
price-ID env vars to be present (non-empty); the shipped placeholders satisfy it.

## Testing

```bash
composer ci:check        # lint/format/type checks, then Pint + the Pest suite
vendor/bin/phpstan analyse
```

## License

MIT.
