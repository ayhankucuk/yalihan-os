# C4 — Channel Fee Separation Implementation Charter

**Charter ID:** `C4_CHANNEL_FEE_CHARTER`  
**Certified Production Code Baseline:** `bc5c0379cf634b43d32f99475952746661e11827`  
**Discovery Reference:** `docs/discovery/C4_CHANNEL_FEE_DISCOVERY.md`  
**Authority:** SAAB Strategic Architecture Board  
**Target Milestone:** ERA-V Phase 2A — Monetization & Finance Core  
**Status:** PROPOSED / AWAITING SAAB DECISION GATE  

---

## 1. Objective

Implement additive, idempotent channel fee separation in the financial completion pipeline so that OTA distribution costs (Airbnb, Booking.com, VRBO) are explicitly snapshotted, recognized in the double-entry ledger, and deducted from the owner's net payout entitlement.

---

## 2. Decision Gate to Lock

Before coding starts, SAAB must select the authoritative policy:
- **[ ] Option 1 (Recommended): OWNER_BORNE** — Net Owner = $\text{Gross} - \text{Channel Fee} - \text{Yalıhan Commission}$
- **[ ] Option 2: NET_OF_OTA** — Yalıhan takes 15% of Net-after-OTA; Owner takes 85% of Net-after-OTA.

---

## 3. Scope of Implementation (C4 Sub-Waves)

### Wave C4.1: Channel Fee Snapshot & Rules Engine
- Add migration: `channel_fee_rate_snapshot`, `channel_fee_amount`, `channel_fee_bearer` to `property_reservations`.
- Define `App\Services\Finance\ChannelFinancialRulesService` or config resolving fee rates:
  - `airbnb`: 15.00% (Host-only) or 3.00% (Split)
  - `booking_com`: 15.00%
  - `direct` / `web`: 0.00%
- Update `ChannexReservationIngestService` / `ReservationService` to stamp the snapshot at booking creation.

### Wave C4.2: Double-Entry Channel Fee Ledger Accrual
- Register `Kanal Komisyon Yükümlülükleri Hesabı` (`LedgerAccount`).
- Add `recordChannelFeeAccrual(PropertyReservation $reservation)` to `FinancialLedgerService`.
- Wire into `ProcessFinancialCompletionJob` (inside same idempotent pipeline).
- Add symmetric reversal in `reverseOwnerPayableAccrual`.

### Wave C4.3: Payout Readiness Surface Update
- Update `PayoutReadinessService`:
  - Include `channel_fee_amount`, `channel_fee_rate`, `net_owner_entitlement` in DTO/array.
- Update `resources/views/admin/finance/payout-ready.blade.php`:
  - Add `Kanal Kesintisi (OTA)` and `Net Ev Sahibi Hakedişi` table columns.

### Wave C4.4: Local & Production Certification Suite
- Unit & Feature tests covering:
  - Inbound booking snapshot creation
  - Multi-channel fee rates (Airbnb 15%, Booking 15%, Direct 0%)
  - Double-entry ledger balance ($\Sigma \text{Debit} = \Sigma \text{Credit}$)
  - Idempotent replay & cancellation reversal
  - Tenant isolation and null snapshot safety

---

## 4. Acceptance Criteria

1. **Deterministic Double-Entry:** Every completed OTA reservation generates balanced entries across Gross Revenue, Channel Liability, Yalihan Revenue, and Owner Liability.
2. **Snapshot Immutability:** Changing channel fee configuration never alters historical reservations.
3. **Zero Impact on Direct Bookings:** Direct reservations continue to have 0% channel fee.
4. **Clean Operator Visibility:** The operator UI clearly distinguishes Gross Amount, Channel Fee, Agency Commission, and Net Owner Payout.
5. **No Mutation of Production State:** Existing legacy reservations with NULL channel snapshots are safely handled without exception.

---

## 5. Certification Gate

$$\mathbf{Status: \quad C4\_CHARTER\_READY \quad (Awaiting \ SAAB \ Policy \ Decision)}$$
