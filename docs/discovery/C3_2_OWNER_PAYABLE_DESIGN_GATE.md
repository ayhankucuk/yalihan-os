# C3.2 — Owner Payable Accrual: Design Gate

> **Baseline**: `976d006` (C3.1 Certified)
> **Type**: READ-ONLY Architecture Analysis → SAAB Design Decision
> **Author**: Kilo / Claude Opus 4.8 (READ-ONLY Mode)
> **Date**: 2026-08-22

---

## 1. Scope of Analysis

C3.1 provides the immutable commission snapshot at reservation time:

```
gross_booking_amount = 100,000 TL
management_model_snapshot = FULL_MANAGEMENT
commission_rate_snapshot = 0.1500

yalihan_commission  = 100,000 × 0.1500 = 15,000 TL
owner_entitlement   = 100,000 − 15,000  = 85,000 TL
```

**C3.2 question**: Can the existing double-entry ledger represent this as:
> *"Yalıhan, ev sahibine 85,000 TL borçludur"*

Answer: **Yes — with one new liability account and one new revenue account.**

---

## 2. Ledger Architecture Reality

### 2.1 Account Model: Name-Based, Not Code-Based

```
App\Models\LedgerAccount
  name: string (unique per tenant)
  tip:   asset | liability | equity | revenue | expense
  tenant_id: nullable (system accounts cross-tenant)
```

**Critical**: No account codes. Accounts identified by name string.

### 2.2 LedgerEntry Structure

```
App\Models\LedgerEntry
  tenant_id
  transaction_group_id  (UUID — links debit/credit pair)
  account_id
  debit_amount, credit_amount, currency
  fx_rate_locked      (DECIMAL 10,6)
  base_amount         (TRY)
  reference_type, reference_id   (polymorphic — e.g. PropertyReservation)
  sebep               (description)
```

**Immutable**: timestamps=false, LedgerEntryObserver blocks updates.

### 2.3 Existing Accounts (created by MoneyCoreSeedData)

| Name | tip | Used For |
|------|-----|---------|
| `Misafir Alacakları Hesabı` | asset | Guest payment receivable |
| `Konaklama / Kira Gelirleri` | revenue | Gross rental revenue |
| `Ana Kasa` | asset | Cash/Bank |
| `Depozito Yükümlülükleri` | liability | Deposit liabilities |

### 2.4 Existing Booking Entry (FinancialLedgerService)

```
DB: Misafir Alacakları Hesabı   100,000 TL   (guest paid — we hold it)
CR: Konaklama / Kira Gelirleri 100,000 TL   (revenue recognized)
sebep: "Rezervasyon Konaklama Kaydı #{$id}"
idempotencyKey: reservation_booking_{$reservation->id}_{$tenantId}
```

This already exists in the system for every confirmed reservation.

---

## 3. C3.2 Required Entries

### 3.1 The Split: Two New Sub-Entries

After the gross booking entry, the system must split the gross revenue into:
- Yalıhan's commission share
- Owner's entitlement share

**Entry 2 — Commission Split** (reduces gross revenue account):
```
DB: Konaklama / Kira Gelirleri   15,000 TL   (revenue reduction)
CR: Komisyon Gelirleri Hesabı   15,000 TL   ← NEW account (revenue)
sebep: "Yalihan Komisyon Tahsili #{$reservation->id}"
idempotencyKey: commission_split_{$reservation->id}_{$tenantId}
```

**Entry 3 — Owner Payable Accrual** (reduces gross revenue account):
```
DB: Konaklama / Kira Gelirleri   85,000 TL   (revenue reduction)
CR: Sahip Yükümlülükleri Hesabı 85,000 TL  ← NEW account (liability)
sebep: "Sahip Tahakkuk #{$reservation->id}"
idempotencyKey: owner_entitlement_{$reservation->id}_{$tenantId}
```

### 3.2 Account Definitions

| Account | Name | tip | Scope |
|---------|------|-----|--------|
| Commission Revenue | `Komisyon Gelirleri Hesabı` | revenue | Tenant-specific |
| Owner Payable | `Sahip Yükümlülükleri Hesabı` | liability | Tenant-specific |

**Design decision**: Both accounts are **tenant-specific** (tenant_id = tenant.id).
Not system accounts. Because owner payable is a tenant-specific obligation.

**Account creation pattern**:
```php
$commissionAccount = LedgerAccount::firstOrCreate(
    ['name' => 'Komisyon Gelirleri Hesabı', 'tenant_id' => $tenantId],
    ['tip' => 'revenue', 'aktiflik_durumu' => true]
);
$ownerPayableAccount = LedgerAccount::firstOrCreate(
    ['name' => 'Sahip Yükümlülükleri Hesabı', 'tenant_id' => $tenantId],
    ['tip' => 'liability', 'aktiflik_durumu' => true]
);
```

---

## 4. Owner (Kisi) Dimension

### 4.1 The Gap

LedgerEntry has **no `kisi_id` field**. Owner dimension is tracked via the chain:

```
LedgerEntry
  └── reference_type = PropertyReservation
  └── reference_id   = reservation_id
        └── reservation.ilan
              └── ilan.ilanSahibi
                    └── kisi (owner)
```

### 4.2 Audit Trail

Owner entitlement is always traceable through:
```
reservation_id → ilan_id → owner_kisi_id
```

For reporting/aging: aggregate `owner_entitlement` by `reservation.ilan.ilanSahibi`.

**This is acceptable for C3.2.** No schema change required. The owner dimension is implicit in the reference chain.

**Deferred to C3.3+**: Explicit `kisi_id` on ledger_entry for direct aging reports.

---

## 5. Commission Rate Scenarios

| Scenario | rate_snapshot | Commission Entry | Owner Entry |
|----------|-------------|-----------------|-------------|
| FULL_MANAGEMENT | 0.1500 | 15,000 TL | 85,000 TL |
| CHECKIN_CHECKOUT | 0.1000 | 10,000 TL | 90,000 TL |
| NONE | 0.0000 | **SKIP** (0 TL) | 100,000 TL |
| CUSTOM (0.12) | 0.1200 | 12,000 TL | 88,000 TL |
| Legacy NULL | `null` | **STOP — no accrual** | **STOP** |

### 5.1 NONE = 0% Rule

When `commission_rate_snapshot == 0.0000`:
- **Do not create Entry 2** (commission amount is 0)
- **Create Entry 3** for full gross amount (owner gets 100%)
- Idempotency check on Entry 2 should be: if entry with key `commission_split_*` exists, skip

### 5.2 Legacy NULL = STOP

When `commission_rate_snapshot === null`:
- Per C3.1 design contract: **no invented financial policy**
- **Do not create any split entries**
- Log: `"Skipping accrual for legacy reservation #{$id}: no commission snapshot"`
- No ledger entries, no accrual, no error

### 5.3 CUSTOM Rate

Same logic as FULL_MANAGEMENT/CHECKIN_CHECKOUT, using the custom rate value.
`getEffectiveCommissionRate()` already resolves the correct rate.

---

## 6. Cancellation Reversal

When a reservation is cancelled, the gross booking entry is reversed. C3.2 must handle cancellation:

**Cancellation entry** (mirrors booking):
```
CR: Misafir Alacakları Hesabı   100,000 TL  (reverses guest receivable)
DB: Konaklama / Kira Gelirleri 100,000 TL  (reverses revenue)
sebep: "Rezervasyon İptal #{$reservation->id}"
idempotencyKey: reservation_booking_reversal_{$reservation->id}_{$tenantId}
```

**C3.2 cancellation reversal**:
```
CR: Konaklama / Kira Gelirleri   15,000 TL  (reverses commission split)
DB: Komisyon Gelirleri Hesabı   15,000 TL

CR: Konaklama / Kira Gelirleri   85,000 TL  (reverses owner accrual)
DB: Sahip Yükümlülükleri Hesabı 85,000 TL

idempotencyKey: commission_split_reversal_{$reservation->id}_{$tenantId}
idempotencyKey: owner_entitlement_reversal_{$reservation->id}_{$tenantId}
```

---

## 7. Idempotency

### 7.1 Key Structure

```
commission_split_{$reservation->id}_{$tenantId}
owner_entitlement_{$reservation->id}_{$tenantId}
commission_split_reversal_{$reservation->id}_{$tenantId}
owner_entitlement_reversal_{$reservation->id}_{$tenantId}
```

### 7.2 Check Logic

```php
$exists = LedgerEntry::withoutGlobalScopes()
    ->where('reference_type', PropertyReservation::class)
    ->where('reference_id', $reservation->id)
    ->where('sebep', 'LIKE', '%Sahip Tahakkuk%')
    ->where('tenant_id', $tenantId)
    ->exists();
```

### 7.3 Job Uniqueness vs. Economic Idempotency

Laravel's `ShouldBeUnique` provides **cache-backed atomic lock** for job deduplication.

**Economic idempotency** is guaranteed by the ledger-level check above:
- If entry already exists → skip silently (idempotent replay)
- If job is retried → lock prevents concurrent execution

**This is sufficient.** Both layers work together.

---

## 8. Currency + FxRate

### 8.1 Multi-Currency Booking

```
gross_booking_amount = 1,000 EUR
fx_rate_locked = 36.50 (TRY/EUR at booking time)
gross_try = 36,500 TL

commission_try = 36,500 × 0.15 = 5,475 TL
owner_try      = 36,500 − 5,475 = 31,025 TL
```

Commission and owner entitlement are **in the same currency as the booking**.

### 8.2 Ledger Entry Fields

```php
[
    'currency'      => 'EUR',              // booking currency
    'fx_rate_locked' => 36.50,           // rate at booking time
    'debit_amount'  => 850.00,           // in EUR
    'base_amount'   => 31025.00,          // in TRY
]
```

Existing `FxService.lockRate()` pattern is already in the codebase.

---

## 9. Tenant Isolation

### 9.1 Design Rule

Every ledger entry **must** carry `tenant_id`. Entries are scoped to their tenant.

### 9.2 Account Creation

```php
LedgerAccount::firstOrCreate(
    ['name' => 'Sahip Yükümlülükleri Hesabı', 'tenant_id' => $tenantId],
    ['tip' => 'liability', 'aktiflik_durumu' => true]
);
```

Each tenant has its own owner payable account. No cross-tenant contamination.

### 9.3 Entry Recording

```php
LedgerEntry::create([
    'tenant_id' => $tenantId,
    'account_id' => $ownerPayableAccount->id,
    'debit_amount' => 0,
    'credit_amount' => $ownerEntitlementTry,
    'currency' => $reservation->currency,
    'fx_rate_locked' => $reservation->booking_fx_rate ?? 1.0,
    'base_amount' => $ownerEntitlementTry,
    'reference_type' => PropertyReservation::class,
    'reference_id' => $reservation->id,
    'sebep' => "Sahip Tahakkuk #{$reservation->id}",
]);
```

---

## 10. C3.1 withoutGlobalScopes() Write Hardening

### 10.1 Current State

In C3.1, the snapshot write uses:
```php
PropertyReservation::withoutGlobalScopes()
    ->where('id', $reservation->id)
    ->update(['management_model_snapshot' => $modelSnapshot, ...]);
```

This bypasses global scopes. For C3.2, the same pattern is used in ledger idempotency checks.

### 10.2 Assessment

| Operation | withoutGlobalScopes() | Assessment |
|-----------|----------------------|------------|
| Snapshot write (C3.1) | Used for idempotency check | Appropriate |
| Ledger idempotency check | Used for entry existence | Appropriate |
| Ledger WRITE | Uses `tenant_id` parameter | Safe |

**C3.2 improvement** (non-blocking, correctness hardening):
```php
// Instead of: where('id', $reservation->id)
// Use:         where('id', $reservation->id)->where('tenant_id', $tenantId)
```

This adds explicit tenant scope to the snapshot write, aligning with SAAB Rule 1.

**Status**: Non-blocking. C3.1 test proves correctness. C3.2 can improve this.

---

## 11. Trigger Point

C3.2 entries must be created **after reservation is confirmed** — inside or after the same transaction that creates the reservation.

**Trigger**: `ReservationCreatedEvent` (already dispatched after C3.1 transaction commits)

**Pipeline**:
```
ReservationCreatedEvent
  → ListenReservationCreated
    → ProcessReservationCreatedJob
      → FinancialLedgerService::recordOwnerPayableAccrual($reservation)
        → Creates commission split entry
        → Creates owner payable entry
```

**Idempotent**: If job retries or event replays, idempotency keys prevent duplicate entries.

---

## 12. SAAB Design Gate Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Account names | `Komisyon Gelirleri Hesabı`, `Sahip Yükümlülükleri Hesabı` | Name-based, tenant-specific |
| Account types | revenue (commission), liability (owner payable) | Correct accounting semantics |
| Owner dimension | Implicit via reference chain | No schema change required for C3.2 |
| NONE = 0% | Full gross → owner payable | Owner gets 100%, no commission entry |
| Legacy NULL | **STOP**, no accrual | C3.1 contract |
| Currency | Same as booking | FxService.lockRate() already exists |
| Cancellation | Mirror reversal entries | Standard double-entry cancellation |
| Trigger | ReservationCreatedEvent | Already exists, already fires after transaction |
| Idempotency | Ledger-level check + ShouldBeUnique job lock | Two-layer protection |

---

## 13. Blockers for Implementation

**None.** The existing ledger system is sufficient for C3.2.

### What exists:
- LedgerAccount model with tenant-specific accounts
- LedgerEntry with polymorphic reference (PropertyReservation)
- FinancialLedgerService with idempotent recording
- Transaction commit → Event dispatch pattern
- FxService for currency handling

### What is needed (C3.2 implementation):
- Two new account names in `LedgerAccount::firstOrCreate()`
- Two new ledger entry types in `FinancialLedgerService`
- `ReservationCreatedEvent` listener integration
- Idempotency key management
- Cancellation reversal logic

---

## 14. Non-Blocking Improvements (C3.2 or later)

| Item | Priority | Notes |
|------|----------|-------|
| `withoutGlobalScopes()` write hardening | C3.2 hardening | Add `tenant_id` to snapshot write query |
| Explicit `kisi_id` on ledger_entry | C3.3+ | For direct owner aging reports |
| Owner Payable aging report | C3.3+ | Based on reservation→ilan→kisi chain |
| Payment against owner payable ledger | C4 | Reduces owner liability balance |

---

## 15. Verdict

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 C3.2 DESIGN GATE: UNANIMOUS GO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 Ledger:    Sufficient — 2 new accounts, existing structure
 Owner dim: Implicit via reference chain — no schema change needed
 NONE=0%:  Full gross → owner payable (correct accounting)
 NULL:      STOP per C3.1 contract
 Cancellation: Mirror reversal entries
 Currency:  FxService.lockRate() already in codebase
 Trigger:   ReservationCreatedEvent (existing)
 Blockers:  NONE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**SAAB can proceed to C3.2 implementation with the decisions above.**

---

## Appendix: Complete Double-Entry Map

### Confirmed Reservation (FULL_MANAGEMENT, 100,000 TL)

```
TX1: Guest Payment (already exists)
  DB: Misafir Alacakları Hesabı      100,000 TL
  CR: Konaklama / Kira Gelirleri     100,000 TL

TX2: Commission Split (C3.2 NEW)
  DB: Konaklama / Kira Gelirleri      15,000 TL
  CR: Komisyon Gelirleri Hesabı       15,000 TL

TX3: Owner Accrual (C3.2 NEW)
  DB: Konaklama / Kira Gelirleri      85,000 TL
  CR: Sahip Yükümlülükleri Hesabı    85,000 TL
```

**Balance check**:
- `Konaklama / Kira Gelirleri`: DR 100,000 − CR 15,000 − CR 85,000 = **0**
- `Misafir Alacakları Hesabı`: DR 100,000
- `Komisyon Gelirleri Hesabı`: CR 15,000
- `Sahip Yükümlülükleri Hesabı`: CR 85,000

### Cancellation Reversal

```
TX4: Cancel Guest Payment
  CR: Misafir Alacakları Hesabı      100,000 TL
  DB: Konaklama / Kira Gelirleri     100,000 TL

TX5: Cancel Commission Split
  CR: Konaklama / Kira Gelirleri      15,000 TL
  DB: Komisyon Gelirleri Hesabı       15,000 TL

TX6: Cancel Owner Accrual
  CR: Konaklama / Kira Gelirleri      85,000 TL
  DB: Sahip Yükümlülükleri Hesabı    85,000 TL
```

**Net effect after cancellation**: All accounts return to zero.
