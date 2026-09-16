# RC-B4 — Production Migration Authorization

**Date:** 2026-09-04
**Branch:** `release-candidate/RC2`
**Owner:** Kilo (production deployment)
**Status:** `AUTHORIZED — PENDING DEPLOY`

---

## Migration File

`database/migrations/2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php`

## Purpose

BACKLOG-8: Add unique composite index on `(ilan_id, display_order)` to prevent concurrent race condition on photo ordering (see REMEDIATION_BACKLOG.md BACKLOG-8).

## Safety Features

1. **Idempotent:** `Schema::hasIndex()` check — skips if index already exists
2. **Preflight duplicate check:** Queries for duplicate `(ilan_id, display_order)` pairs before applying index. Aborts with `RuntimeException` if duplicates found.
3. **Cross-DB compatible:** Uses `Schema::hasIndex()` (works on MySQL + SQLite)
4. **Reversible:** `down()` method drops the index cleanly

## Pre-Deployment Checklist

- [ ] MySQL production DB backup taken (`mysqldump` or equivalent)
- [ ] Verify no duplicate `(ilan_id, display_order)` pairs exist:
  ```sql
  SELECT ilan_id, display_order, COUNT(*) as cnt
  FROM ilan_fotograflari
  WHERE deleted_at IS NULL
  GROUP BY ilan_id, display_order
  HAVING COUNT(*) > 1;
  ```
- [ ] If duplicates found: clean up before migration (assign unique display_order per ilan)
- [ ] Stash/commit all local changes on production server
- [ ] Pull `release-candidate/RC2` branch (or merge to main first)

## Deployment Steps

```bash
# 1. Backup production DB
mysqldump -u <user> -p <database> > backup_pre_backlog8_$(date +%Y%m%d_%H%M%S).sql

# 2. Run migration
php artisan migrate

# 3. Verify index created
php artisan tinker --execute="echo Schema::hasIndex('ilan_fotograflari', 'ilan_fotografi_unique_ilan_display_order', 'unique') ? 'INDEX EXISTS' : 'INDEX MISSING';"

# 4. If rollback needed
php artisan migrate:rollback --step=1
```

## Post-Deployment Verification

- [ ] Index `ilan_fotografi_unique_ilan_display_order` exists on `ilan_fotograflari` table
- [ ] Photo upload/reorder functionality works correctly
- [ ] No application errors in logs

## Rollback Plan

```bash
php artisan migrate:rollback --step=1
```

The `down()` method drops the unique index. Application continues to work without the index (race condition protection is lost, but functionality is unaffected).

## Authorization

- **Code Review:** ✅ Migration reviewed during RC2 branch creation
- **Test Verification:** ✅ SQLite in-memory tests pass (AiCostGuardTest 5/5, Security 67/67)
- **Cross-DB Compatibility:** ✅ `Schema::hasIndex()` verified on SQLite + MySQL
- **Production Auth:** Kilo — deploy after backup + duplicate check

## Related

- REMEDIATION_BACKLOG.md — BACKLOG-8 (Fotoğraf display_order Eşzamanlı Yarışı)
- REMEDIATION_BACKLOG.md — RC-B4 release blocker
