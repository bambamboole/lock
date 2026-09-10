# Deployment

Lock ships as one Docker image (`ghcr.io/bambamboole/lock`) that runs in three roles — web, queue worker, scheduler —
next to Postgres and Redis. `compose.yaml` describes that stack and is what Coolify (or plain `docker compose`) runs.
Staging and production are two separate deployments of the same image with their own database, Redis, and
environment; they never share state.

## The image

- **Base: FrankenPHP (`dunglas/frankenphp:1-php8.4-bookworm`) in classic mode.** One binary serves HTTP and runs PHP,
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

Build args: `PHP_VERSION` (default `8.4`; `composer.json` allows `^8.4`, CI runs 8.5), `NODE_VERSION`, and the
`VITE_REVERB_*` values if a Reverb server is deployed (see [Reverb](#reverb)).

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
| `OIDC_FIRST_PARTY_CLIENT`, `OIDC_RP_CLIENT_ID`, `OIDC_RP_CLIENT_SECRET`                                                         | The console's own OIDC client, printed by `oidc:client --first-party` on the first deploy.                                                                                                         |
| `OIDC_PRIVATE_KEY`, `OIDC_PUBLIC_KEY`, `OIDC_PREVIOUS_PUBLIC_KEY`                                                               | The signing key pair; see [Signing keys](#signing-keys).                                                                                                                                           |
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

1. Provide `APP_KEY`, `APP_URL`, `DB_PASSWORD`, and the mail settings. Leave the `OIDC_*` client and key variables
   empty for now.
2. Start the stack (`docker compose up -d`, or deploy in Coolify). `/up` answers `200` as soon as PHP is up; it does
   not probe the database.
3. Run the release step against the running stack:

    ```bash
    docker compose run --rm app deploy
    ```

    `app:deploy` runs `migrate --force --isolated`, the data migrations (creates the master realm), `permission:sync`,
    and `lattice:discover-cache`. Every step is idempotent and safe to repeat.

4. Generate the signing key pair and put it into the environment (details under [Signing keys](#signing-keys)):

    ```bash
    docker compose run --rm app php artisan oidc:rotate-keys --print
    ```

    Add the printed `OIDC_PRIVATE_KEY` and `OIDC_PUBLIC_KEY` to the deployment variables and redeploy. Until they are
    set, discovery works but `/.well-known/jwks.json` returns `500`.

5. Provision the console's own OIDC client (self-SSO) in the master realm. The command prints the variables; nothing
   is written inside the container:

    ```bash
    docker compose run --rm app php artisan oidc:client --first-party --trusted \
        --name="Lock Console" \
        --redirect-uri="$APP_URL/login/callback" \
        --post-logout-redirect-uri="$APP_URL"
    ```

    Set `OIDC_FIRST_PARTY_CLIENT`, `OIDC_RP_CLIENT_ID`, and `OIDC_RP_CLIENT_SECRET` from the output and redeploy. A
    plain re-run creates a second client; re-run with `--adopt=<client_id>` to update the existing one, and add
    `--rotate` to issue a new secret. (`oidc:install-self` is not usable here: it writes `.env`, which the container
    does not have.)

6. Register the first administrator on the master realm's registration page (`APP_URL/realms/admin/auth/register`),
   verify the address, then grant the global role:

    ```bash
    docker compose run --rm app php artisan admin:grant you@example.com
    ```

    The console is at `APP_URL`. Revoke by clearing the user's global roles in the admin UI.

7. In the console, open the `admin` realm's settings and add the console client id to **Trusted clients** so the
   console login skips the consent screen. (`oidc:install-self` would record it as the realm's first-party client,
   but see step 5.)

Every later release is: pull the new image, `docker compose run --rm app deploy`, restart the three app services.
In Coolify, set `php artisan app:deploy` as the pre-deployment command of the `app` service. Because the migrations
run `--isolated`, concurrently booting instances do not race each other.

## Realms and SaaS clients

The SaaS uses two realms, `staging` and `production`, next to the master realm `admin`. Create them in the console
(**Realms → Create realm**) with exactly those slugs. Each realm then exposes its own issuer, discovery document, and
JWKS:

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

The app resolves `Bambamboole\LaravelOidc\Server\Keys\EnvSigningKeyStore` (`config/oidc.php` does not override
`keys.store`). That has three consequences for the deployment:

- **The key pair lives in the environment and is shared by every realm** on the instance: `OIDC_PRIVATE_KEY`,
  `OIDC_PUBLIC_KEY`, and `OIDC_PREVIOUS_PUBLIC_KEY`. Paste the quoted, `\n`-escaped values `oidc:rotate-keys --print`
  emits verbatim; the store unescapes them.
- **Rotation is an environment change.** `oidc:rotate-keys --print` generates a fresh pair and, when a key is
  configured, prints the current public key as `OIDC_PREVIOUS_PUBLIC_KEY`. Put all three into the deployment, restart
  every app service, and keep the previous public key until every token signed by it has expired (access-token
  lifetime plus the refresh window); then remove it. Without `--print` the command tries to write `.env` and fails
  inside the container with `Unable to read the environment file at [/app/.env]`, which is also why
  `oidc:rotate-keys --if-missing` cannot be part of the container's deploy step.
- **Backup is the environment**, not the database: keep the deployment's variables in whatever secret store Coolify
  or your platform backs up. Losing the private key invalidates every outstanding token and ID token.

The package also ships `DatabaseSigningKeyStore`, which keeps one active key **per realm** in `oidc_signing_keys`
(the table already exists), retires the previous key in place so JWKS keeps serving it, and makes
`oidc:rotate-keys --if-missing` a no-op once a key exists. Switching to it is a one-line change in `config/oidc.php`
(`'keys' => ['store' => DatabaseSigningKeyStore::class]`); with it, step 4 above becomes
`docker compose run --rm app php artisan oidc:rotate-keys --if-missing` (and can join the deploy step), rotation is
`oidc:rotate-keys --force` per realm, and the database backup covers the keys. This is an owner decision because it
changes where every environment — CI and local included — stores its keys.

## Database backups

Postgres is the only state that matters besides the signing keys: users, realms, clients, tokens, sessions, consents,
roles, settings, and the audit log all live there. Back up the `postgres` volume (or run `pg_dump` from the
`postgres` service) on a schedule. The `storage` volume only holds uploaded files, of which there are none today, and
Redis holds only cache, sessions, and queued jobs.

## Reverb

Nothing server-side in the app broadcasts today: no event implements `ShouldBroadcast`, `routes/channels.php` only
authorizes the framework's per-user channel, and the app sends no Lattice notifications. The compose stack therefore
runs no Reverb service and sets `BROADCAST_CONNECTION=log`.

The frontend still calls `configureEcho()` unconditionally in `resources/js/app.tsx` with the `VITE_REVERB_*` values
baked in at build time. In the published image those are empty, so the browser's websocket connection attempt fails
quietly and every page keeps working without live updates. If Reverb is wanted later, add a `reverb` service
(`php artisan reverb:start`), route `wss://` to it, and rebuild the image with the `VITE_REVERB_*` build args.

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
  a `500` on JWKS means the signing key is missing or unreadable.

## Staging versus production

Run them as two independent stacks — separate Coolify projects (or compose project names), separate Postgres and
Redis, separate `APP_KEY`, separate signing keys, separate `APP_URL`. Only the image is shared: pin staging to the
`sha-<commit>` tag you are about to promote, and production to the same tag once staging is verified. Nothing but
those values distinguishes the two, so a staging stack can be recreated from scratch at any time.

## Local trial

```bash
docker compose --env-file docker/local.env -f compose.yaml -f compose.local.yaml up --build -d
docker compose --env-file docker/local.env -f compose.yaml -f compose.local.yaml run --rm app deploy
docker compose --env-file docker/local.env -f compose.yaml -f compose.local.yaml run --rm app php artisan oidc:rotate-keys --print
```

Paste the printed keys into `docker/local.env`, `up -d` again, and open
`http://localhost:8080/realms/admin/.well-known/openid-configuration`. Every value in `docker/local.env` is a public
throwaway; never reuse it.
