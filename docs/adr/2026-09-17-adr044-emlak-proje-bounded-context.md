# ADR-044: Emlak Proje / Team Proje Bounded Context Separation

**Status:** ACCEPTED
**Date:** 2026-09-17
**Implementation Commit:** 3ced67c14041d8e137a858fa719c8fa7dfc5aeae
**Deciders:** Ayhan (Human Decision Owner)
**Task ID:** ADR_CANONICAL_CONVERGENCE_01

---

## Context

Two distinct domain concepts were being conflated under a single identifier ("ADR #006"):

1. **Emlak Proje** — A real-estate development project with developer name (`gelistirici_adi`),
   location, and pricing. Used by the Emlak module for new-development listings.

2. **Team Proje** — An internal team/project management entity. Unrelated domain.

Both used the Turkish word "proje" but represent different bounded contexts. The collision
created confusion in repository governance, test naming, and migration documentation.

---

## Decision

### Canonical Bounded Context Mapping

| Domain Concept | Model Class | Database Table | Module |
|---|---|---|---|
| **Emlak Proje** | `App\Modules\Emlak\Models\Proje` | `emlak_projeleri` | Emlak Module |
| **Team Proje** | `App\Models\Proje` | `projeler` | App (Team Management) |

### Key Invariants

1. **`Ilan::proje()` resolves to `App\Modules\Emlak\Models\Proje`**
   - `App\Models\Ilan` has a `proje()` relation → `App\Modules\Emlak\Models\Proje`
   - Foreign key: `ilanlar.proje_id` → `emlak_projeleri.id`

2. **`App\Models\Proje` (Team Proje) is isolated**
   - Different table (`projeler`)
   - Different namespace (`App\Models\Proje`)
   - No cross-contamination with Emlak module

3. **Migration establishes the schema contract**
   - `2026_09_17_000002_add_proje_id_to_ilanlar_table` adds `proje_id` column
   - This column points to `emlak_projeleri`, not `projeler`

---

## Canonical Chain

```
Ilan (App\Models\Ilan)
  └─ proje() → BelongsTo → App\Modules\Emlak\Models\Proje (emlak_projeleri)
         └─ ilanlar() → HasMany → App\Models\Ilan (ilanlar)

Team Proje (App\Models\Proje) — completely isolated, different table
```

---

## Regression Test Reference

`tests/Feature/Emlak/EmlakProjeBoundedContextTest.php`

Tests all six invariants:
- A: `Ilan::proje()` returns an Emlak Proje instance
- B: `Proje::ilanlar()` returns an Ilan collection (inverse relation)
- C: `ilanlar.proje_id` stores `emlak_projeleri.id`
- D: `Ilan` without `proje_id` has null `proje` relation
- E: Emlak Proje stored in `emlak_projeleri`, not `projeler`
- F: Emlak Proje soft-delete removes from active relation

---

## Historical Note

> ⚠️ This decision was previously referred to as "ADR #006" in some repository
> governance artifacts, creating an identifier collision with the pre-existing
> Channel Manager ADR-006 ("Channel Manager Provider Architecture").
>
> During `ADR_CANONICAL_CONVERGENCE_01`, this decision was assigned **ADR-044**
> to resolve the identifier collision. No architectural changes were made;
> only governance identity was corrected.

---

## Repository / Local Status

| Artifact | Status |
|---|---|
| Migration created | ✅ `2026_09_17_000002_add_proje_id_to_ilanlar_table` |
| Migration applied | TEST_VERIFIED (local test DB) |
| `Ilan::proje()` relation | ✅ |
| `Proje::ilanlar()` relation | ✅ |
| Bounded-context regression tests | ✅ PASS |
| Production applied | **UNKNOWN** |

---

## References

- `database/migrations/2026_09_17_000002_add_proje_id_to_ilanlar_table.php`
- `app/Modules/Emlak/Models/Proje.php`
- `app/Models/Ilan.php` (relation definition)
- `tests/Feature/Emlak/EmlakProjeBoundedContextTest.php`
- Implementation commit: `3ced67c14041d8e137a858fa719c8fa7dfc5aeae`
