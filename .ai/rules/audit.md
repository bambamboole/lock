---
paths:
    - "app/Shared/Audit/**"
---

# Audit

## Two audit trails: activity_log for the admin trail, audit_events for security events

`Audit::record()` writes the console's own trail to `activity_log` (spatie/activitylog): an actor changed a thing, with a subject model and a translated description key.

`App\Shared\Audit\Support\DatabaseAuditSink` writes what the OIDC server raises — sign-ins, MFA, consent, token and key events — to `audit_events`. Those rows carry request provenance (ip, user agent, session, client), have no subject model, survive the deletion of the user and realm they name, and arrive at a far higher volume. Do not merge the two tables.

The sink is named by `config('oidc.audit.sink')`. `mergeConfigFrom` merges only the top level, so `config/oidc.php` has to repeat the package's whole `audit` block (`enabled`, `sink`, `log_channel`), not just the key it overrides. The package's listener swallows whatever the sink throws, so a failure here is silent — a broken sink shows up as missing rows, never as a failed login.

The realm is stamped from `Realm::currentId()` at write time, not from the record. An event raised outside a realm (console provisioning, key rotation) is instance-wide and reads back with `realm_id` null.
