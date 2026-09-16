# P4 — Location and Migration Risk Research

**Document:** `docs/architecture/location-migration-risk-2026-09-06.md`  
**Date:** 2026-09-06  
**Agent:** Kodex (Architect)  
**Context:** PHASE2-ROADMAP.md Priority 4 — production migration safety research  
**Evidence Level:** `DOCUMENTED` (read-only analysis; no production write)  
**Status:** ✅ RESEARCH COMPLETE

---

## 1. MySQL vs SQLite — Location Reconciliation Behavior

### 1.1 FK Constraint Comparison

| Relationship | MySQL | SQLite | Risk |
|---|---|---|---|
| `ilceler.il_id → iller.id` | **No FK defined** | N/A | Data integrity gap |
| `mahalleler.ilce_id → ilceler.id` | FK defined (`ON DELETE CASCADE`) | FK defined | ✅ Consistent |
| `ilanlar.il_id → iller.id` | FK defined (`ON DELETE SET NULL`) | FK defined | ✅ Consistent |
| `ilanlar.ilce_id → ilceler.id` | FK defined (`ON DELETE SET NULL`) | FK defined | ✅ Consistent |
| `talepler.il_id → iller.id` | FK defined (`ON DELETE SET NULL`) | FK defined | ✅ Consistent |
| `talepler.ilce_id → ilceler.id` | FK defined (`ON DELETE SET NULL`) | FK defined | ✅ Consistent |

**Critical finding:** `ilceler.il_id → iller.id` has **no foreign key constraint** in MySQL schema. This is the primary location reconciliation risk.

### 1.2 SQLite Behavior in Test Suite

SQLite enforces FK constraints only when `PRAGMA foreign_keys = ON` is set. Laravel's test database bootstrapping typically enables this, but the absence of `ilceler → iller` FK means:

- **MySQL**: Orphan `il_id` values in `ilceler` table are silently allowed (no FK enforcement)
- **SQLite**: Same behavior — no constraint to enforce
- **Test implication**: No difference in constraint behavior; reconciliation logic must be application-level

### 1.3 Location Canonical IDs (pre-migration state)

The original database had incorrect canonical IDs:

| Entity | Canonical ID | Original DB ID | Status |
|---|---|---|---|
| Muğla (il) | 48 | 1 | ID MISMATCH |
| Bodrum (ilce) | 1 (il_id=48) | 1 (il_id=1) | Parent FK MISMATCH |
| Marmaris (ilce) | — | 2 | Orphan (no canonical) |
| Milas (ilce) | — | 3 | Orphan (no canonical) |
| İstanbul (il) | — | 2 | No canonical record |
| Ankara (il) | — | 3 | No canonical record |

Migration `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php` was designed to fix this by inserting canonical records.

---

## 2. Orphan FK Impact Analysis

### 2.1 Current State (Production Evidence — `BLOKED-reason-report.md`)

```
iller:     3 records (Muğla=id1, İstanbul=id2, Ankara=id3)
ilceler:   5 records (Bodrum=id1, Marmaris=id2, Milas=id3, Beşiktaş=id4, Kadıköy=id5)
mahalleler: 0 records
ilanlar:   0 records
talepler:  0 records
proj_listings: 0 records
```

All referencing tables have **0 records** — no live orphan impact.

### 2.2 Tables Referencing `iller`

| Table | FK to `iller`? | ON DELETE | Record count |
|---|---|---|---|
| `proj_listings` | ✅ yes | SET NULL | 0 |
| `site_apartmanlar` | ✅ yes | SET NULL | 0 |
| `talepler` | ✅ yes | SET NULL | 0 |
| `sites` | ✅ yes | CASCADE | 0 |
| `ilanlar` | ✅ yes | SET NULL | 0 |
| `ilceler` | ❌ NO FK | N/A | 5 |

### 2.3 Risk Assessment

| Risk | Severity | Current Impact | Future Impact |
|---|---|---|---|
| `ilceler.il_id` orphan (no FK) | MEDIUM | LOW (0 records) | MEDIUM if records added |
| Bodrum FK reversal | MEDIUM | RESOLVED by migration (logs original) | LOW |
| Canonical ID collision | MEDIUM | RESOLVED by migration (EXISTS check) | LOW |
| `mahalleler` empty | LOW | None (0 records) | MEDIUM |
| No `ilceler → iller` FK | MEDIUM | LOW (0 records) | MEDIUM if records added |

---

## 3. `bina_yasi` Migration Backward Compatibility Review

### 3.1 Migration: `2026_08_26_000002_fix_bina_yasi_column_type.php`

**Purpose:** Convert `bina_yasi` from MySQL `YEAR` to `unsignedSmallInteger` (building age in years).

**Safety mechanisms:**
1. `if (DB::connection()->getDriverName() !== 'mysql') return;` — SQLite skips safely
2. Guard: throws if column is not `YEAR` type
3. Guard: throws if stale backup table exists
4. Guard: throws if any values are outside reversible YEAR→age windows
5. Backup table: exact rollback via `bina_yasi_year_migration_backup`
6. Down migration: exact restore from backup + MySQL YEAR window for post-migration rows

**Reversibility:**
- ✅ EXACT for rows with original YEAR values (restored from backup)
- ✅ APPROXIMATE for rows created after migration (using MySQL YEAR two-digit windows: 00-69→2000-2069, 70-99→1970-1999)

**Production status:** Applied (batch 48/49) per `production-migration-preflight.md`

### 3.2 Assessment

| Aspect | Verdict |
|---|---|
| Rollback safety | ✅ Backup table ensures exact restore |
| New row corruption | ✅ MySQL YEAR windows handle 1970-2069 |
| SQLite compatibility | ✅ Early return for non-MySQL |
| Idempotency | ✅ Guard throws on stale backup |
| Down migration completeness | ✅ Partial rows handled by YEAR window approximation |

**Backward compatibility: ✅ SAFE** — migration has proper guards, backup, and rollback. No action required.

---

## 4. No-Data-Loss Migration/Reconciliation Plan

### 4.1 Location Canonical Reconciliation

**Migration:** `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php`

**Strategy:** Idempotent, INSERT-only for missing records, no UPDATE/DELETE/TRUNCATE

**Safety rules enforced:**
- ✅ EXISTS check before INSERT (idempotent)
- ✅ TRUNCATE YOK
- ✅ Mevcut overwrite YOK
- ✅ Orphan silme YOK (raporlanir sadece)
- ✅ Bodrum FK log'lanir + rollback'te geri yuklenir
- ✅ Transaction icinde

**Remaining gap:** `ilceler → iller` FK constraint is still not defined in schema. This should be added as a separate, additive migration to enforce referential integrity going forward.

### 4.2 Recommended Action: Add `ilceler → iller` FK Constraint

```php
// Proposed: new migration to add missing FK
Schema::table('ilceler', function (Blueprint $table) {
    $table->foreign('il_id')
        ->references('id')
        ->on('iller')
        ->onDelete('restrict'); // Prevent orphan parent deletion
});
```

**Pre-requisites before adding FK:**
1. Run `ReconcileLocationsCommand` to ensure all `ilceler.il_id` values reference valid `iller.id`
2. Check for orphaned `ilce` records with invalid `il_id`
3. Add only after canonical reconciliation is confirmed complete

### 4.3 TKGM Polygon Persistence — Pre-flight Checklist

Per PHASE2-ROADMAP.md Cross-System Contract Risks:

- [ ] **Blocker:** Location plaka/ID reconciliation must be confirmed BEFORE TKGM polygon persistence
- [ ] **Blocker:** Orphan FK check for `mahalleler` before polygon FK attachment
- [ ] **Blocker:** Confirm `ilceler → iller` FK added before cross-table spatial queries

---

## 5. Summary and Recommendations

### 5.1 Research Findings

| # | Finding | Severity | Action |
|---|---|---|---|
| 1 | `ilceler → iller` FK missing in schema | MEDIUM | Add after reconciliation confirmed |
| 2 | All referencing tables have 0 records | LOW | No immediate action |
| 3 | `bina_yasi` migration safe with backup | LOW | No action needed |
| 4 | Location reconciliation migration idempotent | LOW | No action needed |
| 5 | SQLite behavior consistent with MySQL (no FK to compare) | LOW | No action needed |

### 5.2 Blockers for TKGM / Location Migration

The following must be resolved before any TKGM polygon or production location write:

1. ✅ `2026_08_26_000001` reconciliation migration applied
2. ⬜ Confirm `ilceler` canonical records (all `il_id` reference valid `iller.id`)
3. ⬜ Add `ilceler → iller` FK constraint
4. ⬜ Reconcile `mahalleler` (currently 0 records — need canonical seed)
5. ⬜ Authorize TKGM polygon persistence

### 5.3 Next Steps

| Step | Owner | Status |
|---|---|---|
| Run `ReconcileLocationsCommand` to verify orphan count | Operator | PENDING |
| Add `ilceler → iller` FK migration | Kodex | PENDING |
| Verify `mahalleler` canonical seeding plan | Kodex | PENDING |
| TKGM polygon persistence authorization | Board | PENDING |

---

## 6. Evidence Sources

| Source | Evidence Level | Relevance |
|---|---|---|
| `database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php` | `REPO_VERIFIED` | Migration strategy, safety rules |
| `database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php` | `REPO_VERIFIED` | Backward compatibility |
| `database/schema/mysql-schema.sql` (lines 2989, 3628-3644) | `REPO_VERIFIED` | FK constraint audit |
| `audits/golden-thread-evidence/BLOKED-reason-report.md` | `DOCUMENTED` | Production orphan state (pre-migration) |
| `audits/golden-thread-evidence/production-migration-preflight.md` | `DOCUMENTED` | Production migration status |
| `audits/golden-thread-evidence/tc-gt-06-db-persistence-diagnosis.md` | `DOCUMENTED` | `bina_yasi` migration evidence |

---

*Research complete. No production writes performed. All findings are read-only analysis.*
