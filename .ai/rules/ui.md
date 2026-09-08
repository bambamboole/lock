---
paths:
    - "app/*/Ui/**"
---

# Lattice UI

## The Ui slice is the only place that may touch Lattice

`app/{Domain}/Ui/**` is the Lattice adapter layer. Everything outside it — models, actions, policies, support
classes — must not import `Lattice\…` or another domain's `Ui\…`. `tests/Feature/ArchitectureTest.php` states this
but does not currently enforce it: `expect([$a, $b])->not->toUse($x)` passes as soon as _any_ one source lacks the
dependency, and both rules pass an array. Treat the convention as unchecked until that test is rewritten one
`arch()` per namespace.

## A settings tab is an invokable in Ui/Settings/, registered from the module's provider

`app/{Domain}/Ui/Settings/{Name}Tab.php` is a small `final` class with a single `__invoke()` returning a `Tab`; the
domain's service provider hangs it into the `tenant.settings.tabs` slot with `Lattice::extend(…, priority:)`. That
is how a module contributes UI to a page it does not own: a tab class living outside `App\Tenants` still renders
inside the tenant settings page.
There is no `Settings/Tabs/` subfolder: the `Tab` suffix already says what the class is.

## Resolve models through registered context, never route parameters or session state

`TenantsServiceProvider` registers `tenant` and its dependent model keys with `Lattice::context()`. Definitions use
`$this->contextModel('tenant')` when the key is required and `$this->contextModelOrNull('tenant')` when render-time
authorization should hide a component with missing context. Use `$this->context('tenant')` only when the raw scalar
is genuinely needed; do not query from `contextString()` or read `$request->route()` inside a definition. Signed
definition endpoints may run after the page request, so the registered context remains the source of truth.

Give closure resolvers a concrete model return type. Lattice uses it to map bound route models to registered context
keys, so `{current_tenant}` seeds the `tenant` page frame automatically. Do not override `Page::contextFrame()` just
to translate between those names.

Context resolvers are pure lookups. A resolver also runs while components are merely being built, once per distinct
value, so a side effect in it (switching the user's tenant, setting a locale) fires for records the request is not
about. Request-wide state keyed to the context belongs in `Definition::activated()`, which Lattice runs once per
signed endpoint after the gate has passed; tenant-scoped definitions get it from the
`App\Tenants\Ui\Concerns\ActivatesTenantContext` trait, which makes the context tenant current for the request.
Page loads are covered by `SetTenantUrlDefaults` and `SwitchesCurrentTenant`; a page has no `activated()`.

Declare simple authorization on the definition attribute with the registered subject key:
`#[AsAction('tenants.members.remove', can: TenantPermission::ManageMembers, on: 'tenant')]`. Lattice resolves the
subject before the definition runs and denies a missing subject. Keep `authorize()` only for conditions a gate
subject cannot express, and use the nullable context accessor inside it.

## Reach for the Lattice skills before writing a definition

`lattice-forms`, `lattice-tables`, `lattice-actions`, and `lattice-closures` cover the builder APIs, the validation
and effect models, and how closure parameters resolve. Read the matching one rather than inferring the API from a
sibling class.

## Regenerate TypeScript after changing a wire type

A `#[AsComponent]` / `#[AsField]` / `#[AsColumn]` class is part of the wire format. After changing its public
properties run `php artisan lattice:typescript` and commit `resources/js/lattice/generated.d.ts`. The generator
reads `extra.lattice.discover` from `composer.json` and fails loudly when discovery is empty.
