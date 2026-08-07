# EX-002 Finance Agent — Remediation Brief v1.0

**Version:** 1.0
**Date:** 2026-08-07
**Status:** 🔴 NOT READY — REMEDIATION REQUIRED
**Priority:** 🔴 CRITICAL — DO NOT START EX-003
**Root Cause:** Financial correctness violations + architecture violations
**Deadline:** All BLOCKERs resolved before any new work

---

## SAAB Executive Decision

```
EX-002: 🔴 NOT READY — REMEDIATION REQUIRED

Reason: 9 BLOCKER + 7 HIGH findings from Klio Architecture Review.
The Finance domain has critical issues that could result in wrong payouts to owners.
Starting EX-003 while EX-002 is broken would increase WIP without delivering value.

Decision: WenOX remediates EX-002 only. No new missions until BLOCKER = 0.
```

---

## Remediation Priority Order

### PRIORITY 1 — Financial Correctness (MUST FIX FIRST)

**These issues could result in owners being paid the wrong amount.**

#### BLOCKER 1: Commission Base (PayoutReconciliationService:140)

**Problem:** Commission is calculated on `PropertyReservation.total_price` — NOT on Airbnb gross payout.
These are two different amounts.

**Required Fix:**
```php
// Commission MUST be calculated on the Airbnb gross payout amount
// NOT on the internal PropertyReservation listing price

// Canonical formula:
$grossBookingAmount = $import->gross_booking_amount;
// or for per-reservation reconciliation:
$grossBookingAmount = $reservation->total_price_from_airbnb_payout;
// NOT: $reservation->total_price (Yalihan's internal price)

$yalihanCommission = $grossBookingAmount * $commissionRate; // 10%
```

#### BLOCKER 2: Missing Fields in OwnerPayout (OwnerPayoutPreparationService:77)

**Problem:** `owner_net` formula only has `gross - commission`. Missing: `airbnb_fees`, `refunds`, `adjustments`.

**Required Fix:** Add all fields to OwnerPayout model and complete the formula:

```php
// Complete formula (CANON — from Product Owner decision):
$ownerNet = $grossBookingAmount
    - $airbnbHostServiceFee   // Airbnb's cut
    - $refunds                // Guest refunds
    - $adjustments             // Other adjustments
    - $yalihanCommission;     // Yalıhan's 10%

// NOT the broken formula:
// $totalNet = $totalGross->subtract($totalCommission); // ONLY 2 of 5 components
```

**Required fields on `OwnerPayout`:**
- [x] `airbnb_host_service_fee` — decimal(15,2)
- [x] `refunds` — decimal(15,2)
- [x] `adjustments` — decimal(15,2)
- [x] `gross_booking_amount` — must be from Airbnb payout, NOT PropertyReservation

#### BLOCKER 8: Cleaning Fee Not Handled (CommissionCalculatorService:14-16)

**Problem:** No cleaning fee handling in commission calculation.

**Required Fix:**
```php
// Cleaning fee from Airbnb payout takes PRIORITY
// Ilan.cleaning_fee is ONLY a fallback

$cleaningFee = $import->cleaning_fee
    ?? $reservation->airbnb_cleaning_fee  // if available
    ?? $ilan->cleaning_fee;               // LAST RESORT — fallback

// Commission base includes cleaning fee (confirmed by Product Owner):
$commissionBase = $accommodationRevenue + $cleaningFee + $otherGuestCharges;
$yalihanCommission = $commissionBase * 0.10; // 10%
```

---

### PRIORITY 2 — Aggregate Correctness

#### BLOCKER 3: OwnerPayout Relationship Broken (PayoutReconciliation:86)

**Problem:**
```php
// WRONG — non-deterministic, returns multiple records
return $this->belongsTo(OwnerPayout::class, 'ilan_id', 'ilan_id');

// CORRECT:
return $this->belongsTo(OwnerPayout::class, 'id', 'id');
```

**Also fix:** `hasMany(Reconciliation::class, 'ilan_id')` → should be by `id` or add `owner_payout_id` FK.

---

### PRIORITY 3 — Persistence Separation

**BLOCKERS 4, 5, 6:** All three models call `$this->save()` inside business methods.

**Required Fix — ALL models:**

```php
// AirbnbPayoutImport — remove these methods:
// markAsProcessing(), markAsReconciled(), markAsFailed()

// Instead, use service layer:
$import = AirbnbPayoutImport::create([...]);
$importService->markAsProcessing($import);

// PayoutReconciliation — remove:
// approve(), markAsMatched(), markAsUnmatched(), markAsDisputed()

// OwnerPayout — remove:
// submitForApproval(), approve(), markAsPaid(), cancel()

// All models: REMOVE $this->save() calls from business methods.
// Business methods should ONLY change object state.
// Persistence is the SERVICE's responsibility.
```

**Model methods should return `$this` (fluent) or void.
If state change requires persistence, the CALLING SERVICE does it in a transaction.**

---

### PRIORITY 4 — Tenant-Safe Idempotency

#### BLOCKER 9: Idempotency Without Tenant Scope (PayoutReconciliationService:111)

**Problem:**
```php
// WRONG — no tenant_id
$existing = PayoutReconciliation::where('idempotency_key', $idempotencyKey)->first();

// CORRECT:
$existing = PayoutReconciliation::where('idempotency_key', $idempotencyKey)
    ->where('tenant_id', $import->tenant_id)
    ->first();
```

#### BLOCKER 1 (DB): Missing Unique Constraints

**Problem:** No migration has added `->unique()` constraints on `airbnb_payout_id` and `idempotency_key`.

**Required Fix:** Add migration:
```php
// airbnb_payout_imports
Schema::table('airbnb_payout_imports', function (Blueprint $table) {
    $table->unique(['tenant_id', 'airbnb_payout_id'], 'import_tenant_payout_unique');
});

// payout_reconciliations
Schema::table('payout_reconciliations', function (Blueprint $table) {
    $table->unique(['tenant_id', 'idempotency_key'], 'recon_tenant_idem_unique');
});
```

---

### PRIORITY 5 — Unmatched Reconciliation

#### BLOCKER 7: Unmatched Airbnb Payouts Lost (PayoutReconciliationService:46-54)

**Problem:** If Airbnb sent a payout for 5 reservations but system only has 3, the 2 unmatched reservations are silently ignored. `$stats['unmatched']` is NEVER incremented.

**Required Fix:**
```php
// When Airbnb payout amount > sum of matched reservation amounts:
// 1. Record the unmatched amount
// 2. Increment $stats['unmatched']
// 3. NEVER mark import as "reconciled" if unmatched amount > 0

// Correct flow:
$matchedAmount = $matchedReservations->sum('reservation_amount');
$unmatchedAmount = $import->net_amount - $matchedAmount;

if ($unmatchedAmount > 0) {
    $this->createUnmatchedRecord($import, $unmatchedAmount);
    $stats['unmatched']++;
    // DO NOT mark as reconciled — mark as pending_review
    $import->markAsPendingReview();
} else {
    $import->markAsMatched();
}
```

Also fix BLOCKER 10: `createUnmatchedRecord()` uses one global key for all unmatched records — must use unique key per unmatched reason/amount.

---

### PRIORITY 6 — Currency Safety

#### HIGH: Mixed Currency Risk (OwnerPayoutPreparationService:70-77)

**Required Fix:**
```php
// Validate all reconciliations have the same currency BEFORE summing
$currencies = $reconciliations->pluck('currency')->unique();
if ($currencies->count() > 1) {
    throw new \App\Exceptions\CurrencyMismatchException(
        'Cannot aggregate reconciliations with mixed currencies: ' . $currencies->implode(', ')
    );
}
```

---

### PRIORITY 7 — Test Package (REQUIRED before re-review)

Minimum test coverage required:

| Test | Scope | BLOCKER |
|------|-------|---------|
| Money VO: immutability, currency mismatch, negative amounts | Unit | HIGH |
| CommissionRate VO: 0-100% range, formula correctness | Unit | HIGH |
| PayoutPeriod VO: invalid dates, key determinism | Unit | HIGH |
| CommissionCalculatorService: commission formula with various gross amounts | Unit | BLOCKER |
| AirbnbPayoutImportService: idempotency, tenant isolation, status transitions | Unit | HIGH |
| PayoutReconciliationService: formula correctness, unmatched handling | Unit | BLOCKER |
| OwnerPayoutPreparationService: aggregation, currency mixing | Unit | HIGH |
| FinanceAuditService: log correctness | Unit | MEDIUM |
| Full pipeline: import → reconcile → prepare → approve | Integration | BLOCKER |
| Tenant isolation: cross-tenant access attempts | Integration | HIGH |
| Idempotency: duplicate import, duplicate reconciliation | Integration | HIGH |

**Target: 100% coverage on Value Objects + Service formulas + critical paths.**

---

## Re-Review Exit Criteria

Before Klio re-review, WenOX must confirm:

```
BLOCKER count = 0
HIGH count = 0  OR  each HIGH has explicit SAAB-accepted debt annotation

Required PASS:
[x] Money VO — all edge cases
[x] CommissionRate VO — formula + range
[x] PayoutPeriod VO — invalid periods
[x] CommissionCalculator — 10% on correct gross base
[x] OwnerPayoutPreparation — complete formula (5 components)
[x] Airbnb fees/refunds/adjustments — not double-counted
[x] Tenant isolation — all 3 models + service queries
[x] Idempotency — DB constraints + service checks
[x] Unmatched reconciliation — amount recorded, not silent
[x] Model self-persistence — all $this->save() removed from models
[x] OwnerPayout relationship — belongsTo by id, not ilan_id
[x] Integration tests — full pipeline green
```

---

## Scope Lock

```
DO NOT ADD:
- New features
- New services
- New models
- EX-003 or any other mission

ONLY FIX:
- The 9 BLOCKERs listed above
- The 7 HIGHs listed above
- Test coverage to minimum standard
- Re-review by Klio
```

---

*Remediation Brief v1.0 | SAAB Decision: 🔴 NOT READY*
*WenOX: Fix EX-002 only. No new missions until BLOCKER = 0.*
