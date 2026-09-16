# YALIHAN Drift Sentinel — Phase 1

> **HISTORICAL / SUPERSEDED:** This Phase 1 snapshot predates the production repair and later branch fixes. Its ghost-table and `yayin_tipleri.adi` findings are historical context, not current production status. See `SUPERSEDED.md` and `../EVIDENCE_INDEX.md`.

> Status: `REPO_VERIFIED` (Kilo, 2026-09-02)
> Command: `php artisan yalihan:drift-audit`

## Architecture

```
yalihan:drift-audit
├── YalihanDriftAuditCommand     ← Artisan entry point
├── YalihanDriftAuditService    ← Orchestrator, 7 checks
├── DriftAuditReport             ← Canonical report DTO
├── DriftAuditMarkdownReporter    ← Markdown renderer
└── yalihan-schema-registry.json ← 275-table baseline
```

## Audit Checks

| Check | Status | Evidence | Findings | Notes |
|-------|--------|----------|----------|-------|
| `ghost_tables` | FAIL | REPO_VERIFIED | 3 | Config refs tables not in DB |
| `ghost_fields` | FAIL | LOCAL_RUNTIME_VERIFIED | 1 | `yayin_tipleri.adi` in fillable, not DB |
| `missing_migrations` | WARN | INFERRED | 83 | Legacy tables without migration files |
| `forbidden_aliases` | PASS | REPO_VERIFIED | 0 | No legacy field usage in code |
| `unguarded_tables` | WARN | INFERRED | 4 → 0 | 4 tables added to registry |
| `seeder_coverage` | WARN | REPO_VERIFIED | 18 unique | Field name mismatches in seeders |
| `git_state` | WARN | REPO_VERIFIED | 1 | 62 uncommitted files |

## Ghost Tables (BLOCKED_NEEDS_FIX)

These tables are referenced in `config/schema_guard.php` but do not exist in the database.

| Table | Referenced By | Likely Replaced By |
|-------|---------------|-------------------|
| `property_features` | schema_guard.php | ozellikler |
| `property_templates` | schema_guard.php + GovernanceCore | yayin_tipi_sablonlari |
| `template_feature_assignments` | schema_guard.php | feature_assignments |

Action: Wenox → review references → Antigravity → assess → Codex/SAAB → repair plan.

## Ghost Field (REPO_VERIFIED)

`yayin_tipleri.adi` — Model `YayinTipi` has `adi` in `$fillable` but the DB column is `name`.
Severity: `critical`
Action: Add migration to rename `name` → `adi` OR remove `adi` from fillable.

## Evidence Label Reference

| Label | Meaning |
|-------|---------|
| `REPO_VERIFIED` | Code/repo audit passed |
| `LOCAL_RUNTIME_VERIFIED` | Local SQLite verified |
| `PRODUCTION_VERIFIED` | Live production evidence |
| `INFERRED` | Conclusion from indirect evidence |
| `BLOCKED_NEEDS_FIX` | Blocker found |

## Phase 2 Next Steps (Roo → Wenox → Antigravity → Codex)

1. **Ghost tables**: Wenox reviews all `schema_guard.php` references and determines whether `property_features`/`property_templates`/`template_feature_assignments` are dead code or need migration.
2. **Ghost field**: `yayin_tipleri.adi` — add migration OR fix model fillable.
3. **Seeder coverage**: 18 unique field mismatches. Wenox triages false positives vs real drift.
4. **Hermes integration**: After Phase 2 sign-off, Hermes receives drift events and opens GitHub issues.

## Sentinel Rules

```
Sentinel CAN:  Read ✅  Compare ✅  Test ✅  Report ✅
Sentinel CANNOT: Migrate ❌  Seed ❌  Repair ❌  Deploy ❌
```
