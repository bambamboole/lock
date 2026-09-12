---
paths:
    - "app/Shared/Audit/**"
    - "app/Audit/**"
---

# Audit

## Writing lives in Shared, reading lives in the domain

Every domain writes audit rows, so `Audit::record()`, the two models and the sink stay in `app/Shared/Audit` as the
surface they all reach for. `app/Audit` is what reads them back: the instance and realm trails, the security event
log, and the fragments that render a row's changes. Nothing outside `app/Audit/Ui` queries `Activity` or
`AuditEvent`.

## Two audit trails: activity_log for the admin trail, audit_events for security events

`Audit::record()` writes the console's own trail to `activity_log` (spatie/activitylog): an actor changed a thing, with a subject model and a translated description key. What happens inside a realm — its clients, roles, resources and settings — is recorded with the realm so it lands on that realm's trail; creating and deleting a realm, and the user administration events, stay on the instance trail.

`App\Shared\Audit\Support\DatabaseAuditSink` writes what the OIDC server raises — sign-ins, MFA, consent, token and key events — to `audit_events`. Those rows carry request provenance (ip, user agent, session, client), have no subject model, survive the deletion of the user and realm they name, and arrive at a far higher volume. Do not merge the two tables.

The sink is named by `config('oidc.audit.sink')`. `mergeConfigFrom` merges only the top level, so `config/oidc.php` has to repeat the package's whole `audit` block (`enabled`, `sink`, `log_channel`), not just the key it overrides. The package's listener swallows whatever the sink throws, so a failure here is silent — a broken sink shows up as missing rows, never as a failed login.

The realm is stamped from `Realm::current()` at write time, not from the record: the realm laravel-oidc serves, which for a console command that names none (provisioning, key rotation) is the master realm.
