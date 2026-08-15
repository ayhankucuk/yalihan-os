# SAAB 4.5 — Tenant Isolation Implementation Prerequisites Charter

**Type:** Implementation Prerequisites Charter
**Parent Decision:** SAAB 4.5 — Tenant Isolation Certification
**Parent Commit:** `1c19f47`
**Date:** 2026-08-15
**Status:** 🔲 OPEN
**Scope:** 3 MUST items from SAAB 4.5 §Risks

---

## 1. Mission

SAAB 4.5 Tenant Isolation Certification'da belirlenen 3 MUST implementation ön koşulunu tamamlamak ve TenantScope global scope korumasını tamamlamak.

---

## 2. MUST Items

### MUST 1: property_availabilities Tenant-Scoped Unique Constraint

**SAAB Clause:** SAAB 4.5 §Risks — Risk #1

**Issue:** Cross-tenant race condition potansiyeli. Aynı `property_id + date` kombinasyonu farklı tenant'lar tarafından aynı anda yazılabilir.

**Solution:**
```sql
-- Migration olarak eklenmeli
ALTER TABLE property_availabilities
ADD CONSTRAINT uq_property_availabilities_tenant_date
UNIQUE (property_id, date, tenant_id);
```

**Exit Criteria:**
| # | Criterion | Evidence |
|---|-----------|----------|
| 1a | Migration file created | Migration in `database/migrations/` |
| 1b | Existing duplicate rows cleaned | Pre-migration data fix script |
| 1c | Integration test passes | `PropertyAvailabilityTest` green |

---

### MUST 2: findExistingSync() Race Condition Fix

**SAAB Clause:** SAAB 4.5 §Risks — Risk #2

**Issue:** Atomik işlem garantisi yok. Concurrent request'ler aynı availability slot'u yazabilir.

**Solution:**
```php
// Atomik işlem garantisi eklenmeli
return DB::transaction(function () use ($ilanId, $date) {
    return PropertyAvailability::withoutGlobalScopes()
        ->where('property_id', $ilanId)
        ->where('date', $date)
        ->lockForUpdate()
        ->first();
});
```

**Exit Criteria:**
| # | Criterion | Evidence |
|---|-----------|----------|
| 2a | `lockForUpdate()` in `findExistingSync()` | Code review |
| 2b | `DB::transaction()` wrapping | Code review |
| 2c | Concurrent test passes | Concurrency integration test |

---

### MUST 3: correlationId Idempotency Semantics Documentation

**SAAB Clause:** SAAB 4.5 §Risks — Risk #3

**Issue:** Idempotency garantisi net değil. correlationId semantics dokümante edilmemiş.

**Solution:**
```
correlationId = yalnızca yerel replay anahtarı
OTA düzeyinde idempotency garantisi YOKTUR
processed_at / uniqueId = at-least-once garantisi (exactly-once DEĞİL)
```

**Exit Criteria:**
| # | Criterion | Evidence |
|---|-----------|----------|
| 3a | Docstring updated | Code comment in `ReservationSyncService.php` |
| 3b | ADR entry added | `docs/adrs/ADR-XXX-Idempotency-Semantics.md` |
| 3c | Architecture doc linked | Reference in SAAB 4.5 decision |

---

## 3. Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | MUST 1 migration merged | `git log --oneline --grep="availabilities.*unique"` |
| 2 | MUST 2 code review approved | PR approval + `lockForUpdate()` evidence |
| 3 | MUST 3 ADR merged | `git log --oneline --grep="idempotency"` |
| 4 | SAAB 4.5 Quality Gates updated | All ❌ → ✅ |
| 5 | PROGRESS-TRACKER updated | SAAB 4.5 IMPLEMENTATION PREREQUISITES → ✅ CLOSED |

---

## 4. Exit Criteria

Sprint closes when:
- All 3 MUST items completed
- SAAB 4.5 Quality Gates show all ✅
- PROGRESS-TRACKER reflects closed status
- BEKCI_CHANGELOG updated

---

## 5. Dependencies

| Dependency | Type | Blocker |
|------------|------|---------|
| SAAB 4.5 Certification | Parent Decision | ❌ None — parallel work |
| Availability Sync Sprint | Consumer | ⏳ Benefits from MUST 1+2 |
| PILOT-002 | Reference | ✅ Already handles tenant_id explicitly |

---

## 6. Not in Scope

- Channel Manager sync logic changes
- New reservation operations
- Frontend changes
- TenantScope trait modifications
