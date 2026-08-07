# EX-002 Finance Agent — Program Brief v1.0

**Version:** 1.0
**Date:** 2026-08-07
**Status:** 🟢 AUTHORIZED
**Priority:** 🥇 HIGH
**Estimated Duration:** 1–3 days
**Execution Agent:** WenOX
**SAAB Board:** Kilo
**Mission Owner:** Ayhan

---

## 1. Mission Statement

Automate Airbnb payout reconciliation and owner net payout calculation for Yalıhan Emlak OS.

**End-to-end scope:** Airbnb payout webhook → Import → Reconciliation → OwnerPayout → Human approval → Notification

**Business Impact:** +6% BAI (Bank Ayırlabilir İGelir)
**Automation Target:** Manuel finansal mutabakat operasyonu ortadan kalkacak
**Pilot Scope:** Tek ev sahibi, Ağustos 2026, Airbnb only

---

## 2. Business Rules (Canon — Do Not Change)

### Rule 1: Commission Base
```
gross_booking_amount = accommodation_revenue + cleaning_fee + other_guest_charges
yalihan_commission = gross_booking_amount × 10%
```

### Rule 2: Cleaning Fee
- Airbnb payout/reservation'daki gerçek değer esas alınır
- `Ilan.cleaning_fee` sadece reconciliation fallback
- Fallback kullanıldıysa → insan onayı zorunlu

### Rule 3: Airbnb Platform Fees
- Airbnb host service fee, vergiler, refund, adjustments → owner'a aittir
- Belirsiz adjustment → reconciliation exception (otomatik karar YOK)

### Rule 4: Net Payout Formula
```
owner_net_payout =
    gross_booking_amount
  - airbnb_host_service_fee
  - refunds
  - owner_chargeable_adjustments
  - yalihan_commission
```

### Rule 5: Reconciliation Check
```
actual_airbnb_payout
- yalihan_commission
- post_payout_owner_adjustments
= payable_to_owner
```

---

## 3. Architecture (Three-Layer Model)

```
Airbnb webhook
    ↓
AirbnbPayoutImport (immutable, idempotent)  ← table: airbnb_payout_imports
    ↓
PayoutReconciliation (soft, editable)     ← table: payout_reconciliations
    ↓
OwnerPayout (onaya sunulan yükümlülük)    ← table: owner_payouts
    ↓
Human Approval
    ↓
Ledger Double-Entry (FinancialLedgerService)
    ↓
Owner Notification (Telegram/Email)
```

### Key Principle
- Airbnb ham verisi asla kaybolmaz
- Reconciliation yeniden yapılabilir
- Audit trail baştan sona izlenebilir
- V1 (`FinansalIslem`) write/read path'te DEĞİL — ayrı remediation debt

---

## 4. Canonical Models

### 4.1 AirbnbPayoutImport
| Field | Type | Notes |
|-------|------|-------|
| `id` | bigint PK | |
| `tenant_id` | bigint FK | SAB Rule 1 |
| `airbnb_reservation_id` | string | Airbnb rezervasyon ID |
| `idempotency_key` | string UNIQUE | Airbnb payout ID — dedup |
| `gross_booking_amount` | decimal(15,2) | |
| `accommodation_revenue` | decimal(15,2) | |
| `cleaning_fee` | decimal(15,2) | |
| `airbnb_host_service_fee` | decimal(15,2) | |
| `refunds` | decimal(15,2) | |
| `taxes_withheld` | decimal(15,2) | |
| `adjustments` | decimal(15,2) | |
| `actual_airbnb_payout` | decimal(15,2) | |
| `currency` | string(3) | TRY, USD, EUR |
| `payout_date` | date | |
| `metadata` | json | Ham payload |
| `created_at` | datetime | |
| `updated_at` | datetime | |

### 4.2 PayoutReconciliation
| Field | Type | Notes |
|-------|------|-------|
| `id` | bigint PK | |
| `tenant_id` | bigint FK | SAB Rule 1 |
| `import_id` | bigint FK | AirbnbPayoutImport |
| `reservation_id` | bigint FK | PropertyReservation |
| `ilan_id` | bigint FK | Villa |
| `owner_id` | bigint FK | Owner |
| `reconciliation_status` | enum | pending, matched, mismatch, pending_review |
| `reconciliation_notes` | text | |
| `calculated_at` | datetime | |
| `created_at` | datetime | |

### 4.3 OwnerPayout
| Field | Type | Notes |
|-------|------|-------|
| `id` | bigint PK | |
| `tenant_id` | bigint FK | SAB Rule 1 |
| `reconciliation_id` | bigint FK | PayoutReconciliation |
| `owner_id` | bigint FK | |
| `ilan_id` | bigint FK | |
| `reservation_id` | bigint FK | |
| `gross_booking_amount` | decimal(15,2) | |
| `cleaning_fee` | decimal(15,2) | from Airbnb |
| `yalihan_commission_rate` | decimal(5,2) | default 10.00 |
| `yalihan_commission_amount` | decimal(15,2) | |
| `airbnb_fees` | decimal(15,2) | service fee + taxes |
| `refunds` | decimal(15,2) | |
| `adjustments` | decimal(15,2) | |
| `owner_net_payout` | decimal(15,2) | final amount |
| `currency` | string(3) | |
| `fx_rate` | decimal(10,6) | |
| `reconciliation_status` | enum | matched/mismatch/pending_review |
| `approval_status` | enum | pending/approved/rejected |
| `approved_by` | bigint FK | |
| `approved_at` | datetime | |
| `paid_at` | datetime | |
| `payment_reference` | string | |
| `calculated_by` | string | system / AI model |
| `metadata` | json | Full breakdown |
| `created_at` | datetime | |
| `updated_at` | datetime | |

---

## 5. Deliverables Checklist

### 5.1 Migration
- [ ] `airbnb_payout_imports` table migration
- [ ] `payout_reconciliations` table migration
- [ ] `owner_payouts` table migration
- [ ] Foreign keys + indexes
- [ ] Tenant isolation (SAB Rule 1 on all tables)
- [ ] Idempotency unique constraint on `airbnb_payout_imports.idempotency_key`

### 5.2 Domain Aggregate + Value Objects
- [ ] `AirbnbPayoutImport` model (immutable read, no update)
- [ ] `PayoutReconciliation` model
- [ ] `OwnerPayout` aggregate
- [ ] Value Objects: `Money`, `PayoutBreakdown`, `CommissionRate`
- [ ] Domain Events: `PayoutCalculated`, `ReconciliationMatched`, `PayoutApproved`

### 5.3 Application Services
- [ ] `AirbnbPayoutWebhookHandler` (validation, idempotency, import)
- [ ] `PayoutReconciliationService` (reservation matching)
- [ ] `OwnerPayoutCalculator` (pure calculation, no side effects)
- [ ] `OwnerPayoutApprovalService` (human approval workflow)

### 5.4 Events
- [ ] `AirbnbPayoutReceived` event
- [ ] `ReconciliationCompleted` event
- [ ] `OwnerPayoutApproved` event
- [ ] `OwnerPayoutRejected` event
- [ ] `LedgerEntryCreated` event

### 5.5 Unit Tests
- [ ] `OwnerPayoutCalculator` — tüm formül kombinasyonları
- [ ] `PayoutReconciliationService` — eşleşme + eşleşmeme senaryoları
- [ ] `AirbnbPayoutWebhookHandler` — idempotency, validation
- [ ] Tenant isolation testleri (yanlış tenant erişimi reddedilmeli)
- [ ] Edge case: null cleaning_fee, null refunds, FX conversion

### 5.6 Integration Tests
- [ ] End-to-end: webhook → import → reconciliation → payout
- [ ] Idempotency: aynı payout iki kez geldiğinde tek kayıt
- [ ] Reconciliation mismatch → exception akışı
- [ ] Human approval → ledger entry

### 5.7 Admin Approval UI
- [ ] `OwnerPayoutController` (thin — service'e delegete)
- [ ] Pending payouts list view
- [ ] Detail view (full breakdown)
- [ ] Approve / Reject actions
- [ ] Reconciliation status badges

### 5.8 Metrics & BAI Instrumentation
- [ ] `manual_reconciliation_time_minutes` (önce/sonra)
- [ ] `auto_match_rate` (%)
- [ ] `human_approval_required_rate` (%)
- [ ] `avg_payout_preparation_time_minutes`
- [ ] `bai_contribution` (%)
- [ ] Dashboard widget: Finance Agent metrics

### 5.9 Operations Runbook
- [ ] Kill switch nasıl çalıştırılır
- [ ] Retry nasıl yapılır
- [ ] Reconciliation yeniden nasıl çalıştırılır
- [ ] Yanlış payout nasıl düzeltilir (correction flow)
- [ ] Audit trail sorgulama

### 5.10 Documentation
- [ ] README: mimari, akış, business kuralları
- [ ] API: webhook endpoint spesifikasyonu
- [ ] Pilot plan: tek ev sahibi, Ağustos 2026, adımlar

### 5.11 Executive Report Template
- [ ] Business Outcome (%70): BAI etkisi, manuel zaman tasarrufu
- [ ] Architecture Assessment (%20): SAAB, DDD, tenant isolation
- [ ] Engineering Assessment (%10): test coverage, kod kalitesi
- [ ] Risk & Certification Debt
- [ ] Executive Decision: 🟢 Production Certified / 🟡 Conditional / 🔴 Not Ready

---

## 6. Quality Gates

| Gate | Pass Criterion |
|------|---------------|
| Migration | Idempotent, tenant_id on all tables, FK constraints |
| Domain | Aggregate invariants hold, no side effects in calculators |
| Tests | Unit %80+, Integration tüm happy + failure path |
| SAAB | Thin controller, SAB Rule 1 on all queries |
| Tenant Isolation | Cross-tenant query → 0 results |
| Reconciliation | Manual spreadsheet = System result (pilot validation) |
| Kill Switch | FinansalIslem V1 path'inde DEĞİL |

---

## 7. Out of Scope (v1 Pilot)

- Booking.com / other channel payouts
- Automated bank transfer execution
- Multi-owner batch (tek ev sahibi pilot)
- Historical data backfill beyond August 2026
- Commission rate AI optimization
- EUR/USD auto-conversion (TRY only, FX as-is)

---

## 8. Dependencies

| Dependency | Status | Action |
|------------|--------|--------|
| `PropertyReservation` | ✅ Exists | Validate Airbnb bookings have `booking_fx_rate` |
| `Ilan.ilan_sahibi_id → Kisi → User` chain | ⚠️ Verify | Pre-flight check for pilot owner |
| `CountryFinancialRule` (rental_commission_rate = 10%) | ⚠️ Seed | Seed `rental_commission_rate = 0.10` for TR |
| `FinancialLedgerService` | ✅ Exists | Use for double-entry |
| `FinansalIslem` | ❌ EXCLUDED | V1 remediation separate sprint |

---

## 9. Evaluation Criteria (SAAB Board)

| Dimension | Weight | Question |
|-----------|--------|----------|
| **Business Outcome** | %70 | Manuel finans operasyonu ortadan kalktı mı? BAI ölçülebilir arttı mı? |
| **Architecture Integrity** | %20 | SAAB, DDD, tenant isolation, audit, capability boundary korundu mu? |
| **Engineering Quality** | %10 | Testler, kod kalitesi, bakım kolaylığı yeterli mi? |

**Executive Decision:**
- 🟢 Production Certified
- 🟡 Conditionally Approved
- 🔴 Not Ready

---

## 10. Program Brief Template (For Future Missions)

```markdown
# EX-{NN} {Capability Name} — Program Brief v1.0

## 1. Mission Statement
## 2. Business Rules (Canon)
## 3. Architecture
## 4. Canonical Models
## 5. Deliverables Checklist
## 6. Quality Gates
## 7. Out of Scope
## 8. Dependencies
## 9. Evaluation Criteria
```

---

*Brief Version: 1.0 | Status: 🟢 AUTHORIZED | Execution Agent: WenOX*
