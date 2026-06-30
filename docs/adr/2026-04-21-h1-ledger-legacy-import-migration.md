# ADR: H1 Ledger Legacy Finance Namespace Import Migration

**Date:** 2026-04-21
**Status:** Proposed
**Risk:** MEDIUM
**Scope:** Import-only, 3 files, no-behavior-change

---

## Context

During H1-H5 Duplicate Authority Cleanup analysis, we discovered that `App\Models\Finance\Ledger*` models are already marked `@deprecated` with explicit directives to use `App\Models\Ledger*` instead. The Finance namespace models exist only for backward compatibility (SAB §12: Finance Domain Hardening - Legacy Debt).

**Current State:**
- **SSOT:** `App\Models\LedgerAccount`, `App\Models\LedgerEntry`, `App\Models\LedgerBalance`
- **Deprecated:** `App\Models\Finance\LedgerAccount`, `App\Models\Finance\LedgerEntry`, `App\Models\Finance\LedgerBalance`

**Root Namespace Advantages:**
- Richer schema (FX support: currency, fx_rate_locked, base_amount)
- Transaction grouping (transaction_group_id)
- Immutability support (timestamps=false, manual handling)
- SoftDeletes, HasFactory traits
- Version control (LedgerBalance)

**Finance Namespace Limitations:**
- Missing FX support
- Missing transaction_group
- Missing version control
- No SoftDeletes, HasFactory

**Consumer Graph:**
- Only 3 files still use Finance namespace:
  - `app/Services/Finance/ListingFinanceService.php` (⚠️ Runtime truth: "0 reference dead service")
  - `app/Services/Finance/YalihanTreasury.php`
  - `tests/Feature/Finance/FinanceSmokeSealTest.php`

**Governance Requirements:**
- Immutable core, service-layer mandatory, thin controller
- CQRS projection boundary protection
- No new SSOT creation
- ADR required for structural changes
- Ledger write chain: `FinancialLedgerService::recordDoubleEntry()` → `LedgerEntry::create()` → `LedgerDoubleEntryRecorded` → `UpdateLedgerBalanceProjection`

---

## Decision

Migrate the 3 remaining Finance namespace imports to root namespace (`App\Models\*`).

**Change:**
```php
// BEFORE:
use App\Models\Finance\LedgerAccount;
use App\Models\Finance\LedgerEntry;
use App\Models\Finance\LedgerBalance;

// AFTER:
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerBalance;
```

**Constraints:**
- Import-only change
- No logic modification
- No dead code cleanup in same commit
- No service redesign
- No relation refactor
- Public method signatures unchanged
- Query shape unchanged
- Service flow unchanged
- Response behavior unchanged

---

## Consequences

### Positive
- Removes legacy model authority ambiguity
- Reduces future drift risk
- Aligns with existing SSOT
- Enables future Finance namespace deprecation/removal
- Improves code clarity

### Negative
- Potential silent breakage if relation/cast behavior implicitly depends on Finance namespace
- Test regression if FinanceSmokeSealTest fixture expectations don't match root namespace
- Scope creep risk if "dead code cleanup" mixed with "import migration"

### Neutral
- Write path and projection chain already use root namespace (verified)
- Deprecated bridge remains in place
- Small consumer count (3 files)
- No direct route/request/UI contract impact

---

## Alternatives

### Alternative 1: Keep Dual Namespace
**Pros:** No migration risk, backward compatibility preserved
**Cons:** Perpetuates authority ambiguity, increases future drift risk, violates "no new SSOT" governance

### Alternative 2: Expand Alias/Bridge
**Pros:** Zero code change, transparent compatibility
**Cons:** Hides the problem, doesn't reduce technical debt, complicates future removal

### Alternative 3: Full Finance Namespace Removal
**Pros:** Complete cleanup
**Cons:** Too aggressive, high risk, violates "import-only" constraint, requires broader testing

**Selected:** Import migration (balanced risk/reward)

---

## Implementation Plan

1. **Scope Lock:** H1 = 3 files, import-only
2. **Files:**
   - `app/Services/Finance/ListingFinanceService.php`
   - `app/Services/Finance/YalihanTreasury.php`
   - `tests/Feature/Finance/FinanceSmokeSealTest.php`
3. **Special Handling:**
   - ListingFinanceService: Dead service (0 reference) - import fix only, no refactor
   - YalihanTreasury: Treat as live operational surface
   - FinanceSmokeSealTest: Behavior parity first insurance
4. **Validation:**
   - Finance tests
   - Ledger smoke/balance projection
   - `php artisan test`
   - `php artisan sab:integrity-scan`
   - `php artisan bekci:wizard-contract`
   - `./scripts/quality-gate.sh`

---

## Rollback Plan

- Single commit/package
- Import-level change only
- Revert restores old imports
- No behavior modification to rollback

---

## References

- SAB §12: Finance Domain Hardening (Legacy Debt)
- Ledger write chain: FinancialLedgerService → LedgerEntry → LedgerDoubleEntryRecorded → UpdateLedgerBalanceProjection
- Governance: Immutable core, service-layer, thin controller, CQRS protection
- Runtime truth: ListingFinanceService "0 reference dead service"

---

**Author:** Roo
**Reviewers:** TBD
**Approval:** Pending
