# Deployment

Lock ships as one Docker image (`ghcr.io/bambamboole/lock`) that runs in three roles — web, queue worker, scheduler —
next to Postgres and Redis. `compose.yaml` describes that stack and is what Coolify (or plain `docker compose`) runs.
Staging and production are two separate deployments of the same image with their own database, Redis, and
environment; they never share state.

## The image

- **Base: FrankenPHP (`dunglas/frankenphp:1-php8.5-bookworm`) in classic mode.** One binary serves HTTP and runs PHP,
  so the container needs no nginx/FPM pairing, no supervisor, and no second process to health-check. It runs PHP
  exactly like php-fpm would (a fresh request lifecycle per request), which keeps the app free of worker-mode
  constraints and avoids adding `laravel/octane` as a dependency. Switching to the worker mode later is an
  image-only change.
- Multi-stage: `composer install --no-dev` → Vite build on Node 22 (the version CI uses; nothing in the repo pins
  one) → runtime image. The Vite stage needs `vendor/` because the Lattice Vite plugin discovers component packages
  from `vendor/composer/installed.json`. It runs `npm install` rather than `npm ci` for the same reason CI does: the
  lockfile is generated on macOS and lacks the Linux-only optional packages.
- Runs as the unprivileged `app` user on port `8080`, plain HTTP. TLS terminates at the platform proxy;
  `bootstrap/app.php` already trusts every proxy (`trustProxies(at: '*')`), so there is no `TRUSTED_PROXIES` variable
  to set — narrow that in code if the container is ever reachable without a proxy in front.
- `docker/entrypoint.sh` selects the role from the container command and always builds the bytecode caches first
  (`php artisan optimize`: config, events, routes, views, and the Lattice discovery manifest through its `optimize`
  hook). Route caching works with Lattice's attribute-registered pages.

| Command     | Runs                                                    |
| ----------- | ------------------------------------------------------- |
| `app`       | `frankenphp run` (default)                              |
| `queue`     | `php artisan queue:work --tries=3 --max-time=3600`      |
| `scheduler` | `php artisan schedule:work`                             |
| `deploy`    | `php artisan app:deploy` (one-off release step)         |
| anything    | executed as-is, e.g. `php artisan admin:grant me@x.com` |

Build args: `PHP_VERSION` (default `8.5`, matching CI and Herd; `composer.json` allows `^8.4`) and `NODE_VERSION`.

## Environment

Everything is passed as environment variables; there is no `.env` inside the image. `compose.yaml` wires the
service-internal values (database host, Redis, `LOG_CHANNEL=stderr`, `APP_ENV=production`) and exposes the rest as
`${VAR}` so Coolify can present them as deployment variables.

| Variable                                                                                                                        | Notes                                                                                                                                                                                              |
| ------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_KEY`                                                                                                                       | `docker run --rm ghcr.io/bambamboole/lock php artisan key:generate --show`. Rotating it invalidates sessions and every encrypted value.                                                            |
| `APP_URL`                                                                                                                       | Public HTTPS origin, no trailing slash. It is the OIDC issuer prefix: every realm's issuer is `APP_URL/realms/{slug}`, and it is baked into signed URLs and the first-party client's redirect URI. |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`                                                                                     | Postgres credentials; the compose file feeds the same values to the `postgres` service.                                                                                                            |
| `REALM_MASTER_SLUG`                                                                                                             | Slug of the master realm, `admin` by default. Instance administrators live there; it is created by the data migrations. Do not change it after the first deploy.                                   |
| `OIDC_RP_ISSUER`                                                                                                                | Derived in `compose.yaml` as `APP_URL/realms/REALM_MASTER_SLUG`: the console authenticates against the master realm (see `.ai/rules/realms.md`).                                                   |
| `OIDC_FIRST_PARTY_CLIENT`, `OIDC_FIRST_PARTY_SECRET`                                                                            | The console's own OIDC client. Pick both values yourself; every deploy provisions or re-provisions exactly that client. Leave either empty and the client is left as it is.                        |
| `OIDC_FIRST_PARTY_TRUSTED`                                                                                                      | `true` (the compose default): the console skips its own consent screen. Applied to the master realm on every deploy.                                                                               |
| `OIDC_RP_CLIENT_ID`, `OIDC_RP_CLIENT_SECRET`                                                                                    | Only for signing the console in against another provider. Unset they fall back to the first-party pair above, which is what self-SSO wants.                                                        |
| `LOCK_ADMIN_EMAIL`, `LOCK_ADMIN_PASSWORD`, `LOCK_ADMIN_NAME`                                                                    | The first administrator, created in the master realm, verified, and holding Super Admin. The password is re-applied on every deploy; leave it empty to manage the password in the console instead. |
| `SESSION_SECURE_COOKIE`                                                                                                         | `true` (the compose default). Cookies are only ever sent over HTTPS.                                                                                                                               |
| `SESSION_DOMAIN`                                                                                                                | Leave empty unless the console and the realms are served from different hosts (they are not today).                                                                                                |
| `SESSION_LIFETIME`                                                                                                              | Minutes of idle time before a console session expires (default 120).                                                                                                                               |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Verification, password reset, and invitation mails. `MAIL_SCHEME=smtps` for implicit TLS; otherwise leave it empty and use port 587 (STARTTLS).                                                    |
| `LOG_LEVEL`                                                                                                                     | `info` in production; every line goes to the container's stderr.                                                                                                                                   |
| `ACTIVITYLOG_CLEAN_AFTER_DAYS`                                                                                                  | Retention of the audit log (default 365); enforced daily by the scheduler.                                                                                                                         |

### Session cookie and the realm path

Realms are resolved from the request **path** (`/realms/{slug}`), not the host, so every realm shares one session
cookie: `config/session.php` sets no `path`, and the framework default `/` applies. Until the app scopes
`session.path` per realm (tracked in `.ai/rules/realms.md`), a login in one realm is a login in every realm served by
the same deployment. That is acceptable while every realm on one instance belongs to the same operator; do not host
mutually untrusted tenants on one deployment. Setting `SESSION_PATH` in the environment does not help — it would only
move the single shared cookie.

## First deploy

Nothing is provisioned by hand and nothing is written inside the container: the environment carries the console's
credentials and the first administrator, and `app:deploy` brings the database in line with them.

1. Provide `APP_KEY`, `APP_URL`, `DB_PASSWORD`, and the mail settings, plus the four bootstrap variables. Choose the
   client id and secret and the administrator's address and password yourself — the infrastructure code that
   provisions the instance already holds them, and they are what it authenticates with afterwards:

    ```bash
    OIDC_FIRST_PARTY_CLIENT=lock-console
    OIDC_FIRST_PARTY_SECRET=<generated secret>
    LOCK_ADMIN_EMAIL=you@example.com
    LOCK_ADMIN_PASSWORD=<generated password>
    ```

    The password has to satisfy the master realm's password policy, or the deploy fails with the rule it violated.

2. Start the stack (`docker compose up -d`, or deploy in Coolify). `/up` answers `200` as soon as PHP is up; it does
   not probe the database.

3. Run the release step against the running stack:

    ```bash
    docker compose run --rm app deploy
    ```

    `app:deploy` runs `migrate --force --isolated`, the data migrations (creates the master realm),
    `oidc:rotate-keys --if-missing` for every realm, `permission:sync`, `app:bootstrap`, and
    `lattice:discover-cache`. Every step is idempotent and safe to repeat, so the master realm's signing key exists
    from this point on and no key material goes into the environment (details under [Signing keys](#signing-keys)).

4. Sign in at `APP_URL` with `LOCK_ADMIN_EMAIL` and `LOCK_ADMIN_PASSWORD`. The console client is already provisioned,
   trusted, and recorded as the master realm's first-party client, so the login skips the consent screen.

That is the whole first deploy. Every later release is: pull the new image, `docker compose run --rm app deploy`,
restart the three app services. In Coolify, set `php artisan app:deploy` as the pre-deployment command of the `app`
service. Because the migrations run `--isolated`, concurrently booting instances do not race each other.

### The environment wins, on every deploy

`app:bootstrap` (the step `app:deploy` runs, also runnable on its own) reconciles rather than installs:

- **Every variable is optional, and unset means unmanaged.** No `OIDC_FIRST_PARTY_SECRET` and the client is left
  exactly as it is; no `LOCK_ADMIN_PASSWORD` and an existing administrator's password is not touched; no
  `LOCK_ADMIN_EMAIL` and no user is created. That is what makes the step safe to run on every release.
- **What is set is applied, every time.** A client secret changed in the environment is written to the client on the
  next deploy, and **an administrator password left in the environment is re-applied on every deploy** — a password
  changed in the console is overwritten by the next release. Rotate by changing the variable, or clear the variable
  once the administrator manages their own password.
- **The administrator is created whenever the address is missing** from the master realm, however many other users
  exist. It is not a first-deploy-only step.
- **It converges.** Re-running creates nothing twice: the console client is matched by its provisioning key and then
  by its id, and the administrator by their address in the master realm. The command prints `created`, `updated`,
  `unchanged`, or `skipped (unset)` per item and never prints a secret.

The console client carries the `authorization_code`, `refresh_token`, and `client_credentials` grants: the same
credentials that sign an administrator in interactively are the ones an automated caller uses to get a token from
`APP_URL/realms/admin/oauth/token`. Treat `OIDC_FIRST_PARTY_SECRET` as a production secret.

### Without the bootstrap variables

Leaving `OIDC_FIRST_PARTY_SECRET` empty keeps the manual path open. Provision the client once and set
`OIDC_FIRST_PARTY_CLIENT`, `OIDC_RP_CLIENT_ID`, and `OIDC_RP_CLIENT_SECRET` from the output, then add the client id
to the master realm's **Trusted clients** in the console so the login skips consent:

```bash
docker compose run --rm app php artisan oidc:client --first-party --trusted \
    --name="Lock Console" \
    --redirect-uri="$APP_URL/login/callback" \
    --post-logout-redirect-uri="$APP_URL"
```

A plain re-run creates a second client; re-run with `--adopt=<client_id>` to update the existing one, and add
`--rotate` to issue a new secret. Register the first administrator on the master realm's registration page
(`APP_URL/realms/admin/auth/register`), verify the address, then grant the global role:

```bash
docker compose run --rm app php artisan admin:grant you@example.com
```

`oidc:install-self` is the local-development equivalent of `app:bootstrap` and stays supported there; it is not
usable in a container, because it writes the credentials it generates to a `.env` the image does not have.

## Realms and SaaS clients

The SaaS uses two realms, `staging` and `production`, next to the master realm `admin`. Create them in the console
(**Realms → Create realm**) with exactly those slugs. Creating a realm mints its signing keypair. Each realm then
exposes its own issuer, discovery document, and JWKS:

```text
https://<host>/realms/staging/.well-known/openid-configuration
https://<host>/realms/staging/.well-known/jwks.json
https://<host>/realms/production/.well-known/openid-configuration
```

There is no discovery document at the root: with path-based realm routing every issuer carries its realm.

Clients belong to the realm they are created in, and `php artisan oidc:client` only knows the master realm. Until the
console grows client management for realms, create the SaaS's client inside the target realm with tinker; the plain
secret is only available at creation time:

```bash
docker compose run --rm app php artisan tinker --execute '
$realm = App\Realms\Models\Realm::query()->where("slug", "staging")->sole();
$client = $realm->runAsCurrent(fn () => app(Bambamboole\LaravelOidc\Server\Clients\ClientRepository::class)
    ->createAuthorizationCodeGrantClient("SaaS staging", ["https://staging.saas.example/auth/callback"]));
echo json_encode(["client_id" => $client->client_id, "client_secret" => $client->plainSecret]), PHP_EOL;
'
```

Point the SaaS's staging environment at the `staging` realm issuer and its production environment at the
`production` realm issuer. Whether both realms live on one Lock deployment or the staging realm lives on a separate
staging Lock (to rehearse Lock upgrades themselves) is a topology choice; the compose file supports either.

## Signing keys

Keys live in the `oidc_signing_keys` table, never in the environment. There is **one keypair per realm**: the realm
is the issuer, so a realm signs with its own key and publishes only that key at its own JWKS endpoint.

- **Creation is part of the deploy.** `app:deploy` runs `oidc:rotate-keys --if-missing` for every realm, so the
  master realm has a key from the first deploy on. A realm created later in the console gets its keypair when it is
  created; the deploy step is the safety net for a realm that somehow has none.
- **Rotation is `oidc:rotate-keys`** (no flag), run with the target realm current:

    ```bash
    docker compose run --rm app php artisan tinker --execute '
    App\Realms\Models\Realm::query()->where("slug", "admin")->sole()
        ->runAsCurrent(fn () => Illuminate\Support\Facades\Artisan::call("oidc:rotate-keys", ["--force" => true]));
    '
    ```

    The new key signs from that moment; the previous one is retired but stays in the realm's JWKS under its own
    `kid`, so tokens it signed keep verifying. Delete the retired row once every token signed by it has expired
    (access-token lifetime plus the refresh window). No restart is needed — nothing is cached in the environment.

- **The private key is encrypted at rest with `APP_KEY`** (the model casts it as `encrypted`). Losing `APP_KEY`
  therefore loses every signing key along with the sessions and other encrypted values, and every realm has to be
  re-keyed with `oidc:rotate-keys`, which invalidates every outstanding token and ID token.
- **Backup is the database backup.** There is nothing else to keep — but the dump is only usable together with the
  `APP_KEY` that was current when it was taken.

## Database backups

Postgres is the only state that matters: users, realms, clients, signing keys, tokens, sessions, consents,
roles, settings, and the audit log all live there. Back up the `postgres` volume (or run `pg_dump` from the
`postgres` service) on a schedule. The `storage` volume only holds uploaded files, of which there are none today, and
Redis holds only the cache and queued jobs. Sessions are rows in Postgres on purpose: the account page lists a
user's browser sessions and signs single ones out, which the Redis driver cannot serve.

## Monitoring

- **Logs** go to stderr as JSON (Caddy access log) and Laravel's `stderr` channel; let the platform collect them.
- **Health**: `GET /up` on port 8080 (the Docker `HEALTHCHECK` and the compose healthcheck use it). `queue:monitor`
  fails the `queue` container's healthcheck when the Redis queue backs up past 1000 jobs.
- **Login failures, token issuance, admin changes**: the OIDC package and the app record them in the activity log
  (`App\Shared\Audit\Models\Activity`, browsable under **Activity** in the console; the package's `oidc: audit …`
  lines also reach the log stream). Alert on spikes of failed logins per realm from that table. Retention is
  `ACTIVITYLOG_CLEAN_AFTER_DAYS`; the scheduler runs `activitylog:clean` daily along with `oidc:purge` (expired
  tokens and codes), `oidc:dispatch-expired-session-logouts`, `oidc:prune-authentication-contexts`, and
  `lattice:notifications:prune`.
- **Discovery**: probe `APP_URL/realms/<slug>/.well-known/openid-configuration` and `.well-known/jwks.json` per realm;
  a `500` on JWKS means the realm has no signing key (run `app:deploy` again) or `APP_KEY` no longer decrypts it.

## Staging versus production

Run them as two independent stacks — separate Coolify projects (or compose project names), separate Postgres and
Redis, separate `APP_KEY`, separate `APP_URL`. Signing keys follow the database, so they are separate by
construction. Only the image is shared: pin staging to the
`sha-<commit>` tag you are about to promote, and production to the same tag once staging is verified. Nothing but
those values distinguishes the two, so a staging stack can be recreated from scratch at any time.

## Local trial

```bash
docker compose --env-file docker/local.env -f compose.yaml -f compose.local.yaml up --build -d
docker compose --env-file docker/local.env -f compose.yaml -f compose.local.yaml run --rm app deploy
```

Then open `http://localhost:8080/realms/admin/.well-known/openid-configuration` and its `jwks.json`, which the deploy
step has already keyed, and sign in at `http://localhost:8080` with the `LOCK_ADMIN_EMAIL` and `LOCK_ADMIN_PASSWORD`
from `docker/local.env`. The same file's client id and secret get a machine token:

```bash
curl -s -X POST http://localhost:8080/realms/admin/oauth/token \
    -d grant_type=client_credentials -d client_id=lock-console -d client_secret=local-console-secret
```

Every value in `docker/local.env` is a public throwaway; never reuse it.
