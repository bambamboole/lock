---
paths:
    - "app/*/Observers/**"
---

# Observers

## Model lifecycle hooks live in an observer, not a boot() closure

A model's `creating`/`updating`/`created`/… hooks belong in `app/{Domain}/Observers/{Model}Observer.php`, attached with `#[ObservedBy(...)]` on the model — never a `protected static function boot()` closure block. Helper logic the hook needs (slug generation, notification routing) moves into the observer as a private method, so the model keeps only its relations, casts, and query helpers.

`app/{Domain}/Observers/**` is covered by the architecture test's no-Lattice rule like everything outside a Ui slice.
