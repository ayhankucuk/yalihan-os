# EX-002 Finance Agent — Discovery Findings

**Date:** 2026-08-07
**Agent:** Kilo (AI Workspace Agent)
**Project:** Yalıhan Emlak OS
**Discovery Mode:** EX-002 Finance Agent — Airbnb Payout Reconciliation
**Status:** 🟢 AUTHORIZED — All blocking decisions resolved (v1.0)

> **SAAB Review (2026-08-07):** Discovery güçlü; 4 kritik karar ürün sahibi tarafından kesinleştirildi. Implementasyon yetkili.

---

## 1. Inventory

### 1.1 Financial Models Found

| # | File Path | Class | Table | Domain | Notes |
|---|-----------|-------|-------|--------|-------|
| 1 | `app/Modules/Finans/Models/Komisyon.php` | `Komisyon` | `komisyonlar` | Finans Module (V1) | Rental/sales commission, split-commission support, **has tenant_id** |
| 2 | `app/Modules/Finans/Models/FinansalIslem.php` | `FinansalIslem` | `finansal_islemler` | Finans Module (V1) | Generic financial transactions, **NO tenant_id** |
| 3 | `app/Models/Finance/Commission.php` | `Commission` | `commissions` | Finance Domain (V2) | SAB §12 hardened, tenant-scoped, office/agent share split |
| 4 | `app/Models/FinancialTransaction.php` | `FinancialTransaction` | `financial_transactions` | Finance Domain (V2) | Immutable audit trail, FX support, reservation-linked |
| 5 | `app/Models/CountryFinancialRule.php` | `CountryFinancialRule` | `country_financial_rules` | Finance Domain (V2) | DB-driven rates (rental, sales, advisory, tax) |
| 6 | `app/Models/OwnerReportRow.php` | `OwnerReportRow` | `owner_report_rows` | Finance Domain (V2) | Read-model projection for owner statements |

### 1.2 Financial Services Found

| # | File Path | Class | Role | Tenant-Aware |
|---|-----------|-------|------|--------------|
| 1 | `app/Modules/Finans/Services/KomisyonService.php` | `KomisyonService` | AI-powered commission suggestion/calculation (V1) | No (uses auth context) |
| 2 | `app/Modules/Finans/Services/FinansalIslemManager.php` | `FinansalIslemManager` | CRUD manager for `FinansalIslem` (V1), GuardsAgentWrites | No |
| 3 | `app/Services/Finance/CommissionCalculator.php` | `CommissionCalculator` | Commission projection and payout (V2), **TenantContextResolver DI** | Yes |
| 4 | `app/Services/Finance/YalihanTreasury.php` | `YalihanTreasury` | Canonical finance authority, atomic payout flows (V2), **TenantContextResolver DI** | Yes |
| 5 | `app/Services/FinancialLedgerService.php` | `FinancialLedgerService` | Double-entry ledger, SAB Phase 15, idempotency, FX lock | Yes |
| 6 | `app/Services/CountryFinancialService.php` | `CountryFinancialService` | DB-driven rule engine for country-specific rates | No |

### 1.3 Reservation / Booking Models Found

| # | File Path | Class | Table | Key Fields |
|---|-----------|-------|-------|-----------|
| 1 | `app/Models/PropertyReservation.php` | `PropertyReservation` | `property_reservations` | `ilan_id`, `islem_tutari`, `booking_fx_rate`, `booking_currency`, `finansal_durum`, `depozito_*` |
| 2 | `app/Models/IlanReservation.php` | `IlanReservation` | `property_reservations` (same table) | Same as above, `total_amount` field |
| 3 | `app/Models/YazlikRezervasyon.php` | `YazlikRezervasyon` | `yazlik_rezervasyonlar` | `ilan_id`, `toplam_fiyat`, `check_in`, `check_out`, `kapora_tutari`, `rezervasyon_durumu` |

### 1.4 Property / Villa Models

| # | File Path | Class | Key Fields for EX-002 |
|---|-----------|-------|----------------------|
| 1 | `app/Models/Ilan.php` | `Ilan` | `ilan_sahibi_id` (FK to `Kisi`), `cleaning_fee`, `rental_currency`, `rental_enabled` |

### 1.5 Key Relationships

```
Airbnb webhook (external)
    ↓
PropertyReservation (property_reservations) ←→ Ilan (ilanlar) ←→ Kisi (ilan_sahibi_id)
    ↓                                                        (owns the villa)
FinancialTransaction (financial_transactions)
    ↓
FinancialLedgerService (double-entry: debit/credit)
```

**Critical Link:** `PropertyReservation.ilan_id` → `Ilan.ilan_sahibi_id` → `Kisi.id`

---

## 2. Existing Structures Analysis

### 2.1 V1 — Finans Module (Legacy)

**Komisyon (`komisyonlar`):**
- Supports: `satis`, `kiralama`, `danismanlik` commission types
- Hardcoded rates: `satis=3%`, `kiralama=1%`, `danismanlik=2%`
- Split commission fields: `satici_komisyon_*`, `alici_komisyon_*`
- Has `tenant_id` (added 2025-11-26 migration)
- Status: `hesaplandi` → `onaylandi` → `odendi`
- **Gap:** No Airbnb payout, no cleaning fee, no owner net payout fields

**FinansalIslem (`finansal_islemler`):**
- Generic: `komisyon`, `odeme`, `masraf`, `gelir`, `gider`
- AI audit fields: `ai_inceleme_gerekli`, `ai_modeli`, `ai_dogrulama_durumu`
- **Gap:** No `tenant_id` (CRITICAL VIOLATION of SAB Rule 1)
- **Gap:** No booking_id, no villa_id, no cleaning_fee

### 2.2 V2 — Finance Domain (SAB §12 Hardened)

**Commission (`commissions`):**
- Tenant-scoped via `HasCountryScope` + explicit `tenant_id` in fillable
- Office/agent share: `ofis_tutari`, `danisman_tutari`
- Payment state enum: `PENDING → APPROVED → PAID`
- FX-aware: `base_currency`, `display_currency`
- **Gap:** No Airbnb payout, no cleaning fee, no owner net payout

**FinancialTransaction (`financial_transactions`):**
- Immutable audit trail (no soft delete)
- `property_id`, `reservation_id` links
- FX lock mechanism: `fx_rate_locked`
- `islem_tipi`, `islem_durumu`
- **Gap:** No commission calculation, no cleaning fee ownership logic

**FinancialLedgerService:**
- Double-entry: debit account vs credit account
- Idempotency via `idempotency_key`
- FX conversion to TRY via `FxService`
- SAB Phase 15 compliant
- **Gap:** No Airbnb-specific payout reconciliation logic

**CountryFinancialRule (`country_financial_rules`):**
- DB-driven rates: `rental_commission_rate`, `sales_commission_rate`, `advisory_fee_rate`, `tax_rate`
- Per-country configuration
- **Gap:** Does not distinguish Airbnb channel, no cleaning fee rule

### 2.3 V1/V2 Overlap Analysis

| Dimension | V1 (Finans Module) | V2 (Finance Domain) | Gap |
|-----------|---------------------|---------------------|-----|
| Tenant isolation | Partial (Komisyon has it, FinansalIslem does NOT) | Full | FinansalIslem is a SAB Rule 1 violation |
| Airbnb payout | Not modeled | Not modeled | **CRITICAL GAP** |
| Cleaning fee | Not modeled | Not modeled (only on Ilan) | **CRITICAL GAP** |
| Owner net payout | Not modeled | Not modeled | **CRITICAL GAP** |
| Booking → Villa → Owner chain | Not modeled | Not modeled | **CRITICAL GAP** |
| Commission calculation | Hardcoded 1-3% | Configurable per tenant | Overlap: both exist |
| Audit trail | Soft delete only | Immutable (no soft delete) | Inconsistent |
| FX support | None | Yes (via FxService) | V2 wins |
| Human approval threshold | Manual | No automated threshold | Gap |

### 2.4 Tenant Isolation Assessment

| Model | tenant_id | Guarded By |
|-------|-----------|-----------|
| `komisyonlar` | Yes (migration 2025-11-26) | `BelongsToTenant` trait |
| `finansal_islemler` | **NO** | None — **SAB Rule 1 Violation** |
| `commissions` | Yes | `TenantContextResolver` DI in services |
| `financial_transactions` | Yes (implicit via `HasCountryScope`) | `HasCountryScope` |
| `country_financial_rules` | Via country scope | `HasCountryScope` |
| `owner_report_rows` | Via country scope | `HasCountryScope` |
| `property_reservations` | Yes | `HasCountryScope` |

---

## 3. Business Rules Mapping

### 3.1 Core EX-002 Business Rules (Stated)

| Rule | Value | Source | Gap |
|------|-------|--------|-----|
| Yalıhan commission rate | **10% of booking total** | Discovery target spec | Not hardcoded anywhere; must be seeded in `country_financial_rules` |
| Cleaning fee ownership | **Belongs to owner (not Yalıhan)** | Discovery target spec | `Ilan.cleaning_fee` exists but no ownership chain modeled |
| Net payout formula | `booking_total - commission - cleaning_fee = owner_amount` | Discovery target spec | Not implemented anywhere |
| Airbnb payout webhook | Trigger for reconciliation | Discovery target spec | No webhook handler exists |
| Payment timing | Not specified | — | Requires clarification from product |
| Human approval threshold | Not specified | — | Requires clarification from product |
| Currency conversion | If applicable | — | `FxService` exists, `booking_fx_rate` on `PropertyReservation` |

### 3.2 CountryFinancialRule — Current Configuration

```
country_financial_rules table:
  rental_commission_rate  → currently used for rental commissions
  sales_commission_rate   → currently used for sales commissions
  advisory_fee_rate       → GR Golden Visa advisory
  tax_rate                → KDV/VAT layer
```

**For EX-002:** The `rental_commission_rate` column would hold the **10% Yalıhan commission** for Airbnb bookings. This is already the correct column — no schema change needed if Airbnb bookings flow through `CountryFinancialService`.

### 3.3 Cleaning Fee — Current State

`Ilan.cleaning_fee` exists as a hybrid field. However:
- There is **no separation** between "Yalıhan-owned cleaning fee" vs "owner-owned cleaning fee"
- Cleaning fee is currently just a price component on the listing
- For EX-002: cleaning fee from Airbnb payout must be **passed through to owner**, not retained by Yalıhan

### 3.4 Missing Business Rules

1. **Airbnb payout ingestion:** No model/service handles incoming Airbnb payout data
2. **Cleaning fee pass-through:** No rule distinguishes cleaning fee as owner income vs Yalıhan income
3. **Net payout calculation:** No service computes `airbnb_payout - commission - cleaning_fee`
4. **Reconciliation:** No reconciliation check between expected payout and actual Airbnb payout
5. **Approval threshold:** No automated human approval workflow for amounts above threshold
6. **Payment timing:** No scheduled payout to owner based on `payment_date`

---

## 4. Gap Analysis

### 4.1 Functional Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| No Airbnb payout model/table | **CRITICAL** | No place to store raw Airbnb payout data per booking |
| No booking → villa → owner chain for finance | **CRITICAL** | Cannot trace Airbnb booking to responsible owner |
| Cleaning fee pass-through not modeled | **CRITICAL** | Cannot distinguish owner-income from platform-income |
| Net payout calculation absent | **CRITICAL** | No service computes the EX-002 formula |
| No reconciliation logic | **HIGH** | No comparison between expected vs actual payout |
| No human approval workflow | **HIGH** | No threshold-based approval gate |
| FinansalIslem has no tenant_id | **HIGH** | SAB Rule 1 violation — cross-tenant data leak risk |
| Airbnb webhook handler absent | **HIGH** | No endpoint/service to receive Airbnb payout notifications |

### 4.2 Data Model Gaps

| Missing Field | Belongs In | Reason |
|---------------|-----------|--------|
| `airbnb_payout_amount` | `property_reservations` or new `OwnerPayout` | Raw Airbnb payout per booking |
| `airbnb_payout_currency` | `property_reservations` or new `OwnerPayout` | Currency of payout |
| `airbnb_payout_date` | `property_reservations` or new `OwnerPayout` | When Airbnb paid Yalıhan |
| `cleaning_fee_amt` | `property_reservations` or new `OwnerPayout` | Cleaning fee extracted from booking |
| `yalihan_commission_amt` | `property_reservations` or new `OwnerPayout` | Yalıhan's 10% cut |
| `owner_net_payout_amt` | `property_reservations` or new `OwnerPayout` | Amount owed to owner |
| `reconciliation_status` | New `OwnerPayout` | matched / mismatch / pending_review |
| `approval_status` | New `OwnerPayout` | pending / approved / rejected |
| `approved_by` | New `OwnerPayout` | User who approved |
| `paid_at` | New `OwnerPayout` | When owner was paid |

### 4.3 Tenant Isolation Gaps

1. **`finansal_islemler` table lacks `tenant_id`** — the only table without it in the Finans module. Must add via migration before any EX-002 work.
2. **`FinansalIslemManager`** does not enforce tenant scope in queries.

### 4.4 Audit Trail Gaps

1. **`financial_transactions`** is immutable (good), but **`finansal_islemler`** uses soft-delete (inconsistent).
2. No audit trail for the **net payout calculation** itself (who calculated it, when, with what inputs).
3. No idempotency mechanism for **Airbnb payout ingestion** (duplicate webhook protection missing).

---

## 5. Canonical Model Decision

### 5.1 Recommended: `OwnerPayout` (New Canonical Model)

**Rationale:** The existing models (`Komisyon`, `Commission`, `FinansalIslem`) are all **agent/sales commission** focused. None of them support the Airbnb payout → owner net payout chain. A new canonical model is required.

**Proposed canonical model: `OwnerPayout`**

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `tenant_id` | bigint FK | **Tenant isolation (SAB Rule 1)** |
| `owner_id` | bigint FK | `users.id` — the villa owner |
| `ilan_id` | bigint FK | `ilanlar.id` — the villa |
| `reservation_id` | bigint FK | `property_reservations.id` — the booking |
| `channel` | string | `'airbnb'` (extensible to `'booking_com'`, etc.) |
| `booking_total` | decimal(15,2) | Total booking amount from channel |
| `cleaning_fee` | decimal(15,2) | Cleaning fee (belongs to owner) |
| `yalihan_commission_rate` | decimal(5,2) | Commission rate (default: 10.00) |
| `yalihan_commission_amount` | decimal(15,2) | Yalıhan's cut = `booking_total * rate` |
| `owner_net_payout` | decimal(15,2) | `booking_total - yalihan_commission_amount - cleaning_fee` |
| `currency` | string(3) | TRY, USD, EUR |
| `fx_rate` | decimal(10,6) | FX rate if currency != TRY |
| `airbnb_payout_gross` | decimal(15,2) | Raw amount Airbnb paid Yalıhan |
| `airbnb_payout_net` | decimal(15,2) | Airbnb net after their fees |
| `airbnb_payout_date` | date | When Airbnb paid Yalıhan |
| `reconciliation_status` | enum | `pending`, `matched`, `mismatch` |
| `reconciliation_notes` | text | If mismatch, notes |
| `approval_status` | enum | `pending`, `approved`, `rejected` |
| `approved_by` | bigint FK | `users.id` |
| `approved_at` | datetime | |
| `paid_at` | datetime | When owner was actually paid |
| `payment_reference` | string | Bank transfer reference, etc. |
| `idempotency_key` | string | Airbnb payout ID for dedup |
| `calculated_by` | string | `'system'` or AI model name |
| `metadata` | json | Raw Airbnb payload for audit |
| `created_at` | datetime | |
| `updated_at` | datetime | |

**Table name:** `owner_payouts`
**V1/V2 boundary:** This is **pure V2** — SAB §12 compliant from day one.
**Migration path:** New migration, no V1 data to migrate.

### 5.2 Three-Layer Architecture (SAAB Recommended)

> **Change from v0.1:** Single `OwnerPayout` model replaced with three-layer separation per SAAB review.

| Layer | Model | Table | Immutable | Purpose |
|-------|-------|-------|-----------|---------|
| Import | `AirbnbPayoutImport` | `airbnb_payout_imports` | Yes | Airbnb'den gelen ham veri, idempotency key ile |
| Reconciliation | `PayoutReconciliation` | `payout_reconciliations` | Soft | Rezervasyon eşleştirme sonucu |
| Obligation | `OwnerPayout` | `owner_payouts` | Soft | Onaya sunulan ödeme yükümlülüğü |

```
Airbnb webhook
    ↓
AirbnbPayoutImport (immutable, idempotent)
    ↓
PayoutReconciliation (soft, editable logic)
    ↓
OwnerPayout (onay sonrası ödeme talimatı)
```

**Avantaj:** Airbnb ham verisi asla kaybolmaz, eşleştirme yeniden yapılabilir, audit trail sağlam.

### 5.3 Extension vs New Model

| Option | Pros | Cons |
|--------|------|------|
| Extend `FinancialTransaction` | Shares infrastructure, FX, ledger | Bloats generic model with Airbnb-specific fields |
| Extend `Commission` | Reuses payout flow | Wrong domain (agent commission vs owner payout) |
| **New `OwnerPayout`** | Clean domain, Airbnb-specific, SAB-compliant | New infrastructure |

**Decision: New `OwnerPayout` model.** Clean separation between agent commission domain and owner payout domain.

### 5.3 Relationship to Existing Infrastructure

```
OwnerPayout
  ├── belongsTo Ilan (ilan_id)
  ├── belongsTo User/owner (owner_id) — derived from Ilan.ilan_sahibi_id → Kisi → Kisi.user_id
  ├── belongsTo PropertyReservation (reservation_id)
  ├── hasMany LedgerEntry via FinancialLedgerService
  └── morphTo for audit trail
```

---

## 6. Proposed Canonical Flow

### 6.1 End-to-End Flow: Airbnb Payout → Owner Net Payout

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: AIRBNB PAYOUT WEBHOOK RECEIVED                          │
│ Endpoint: POST /api/webhooks/airbnb/payout                      │
│ Handler: AirbnbPayoutWebhookHandler                             │
│ Action: Validate payload, extract booking reference             │
│         Generate idempotency_key from Airbnb payout ID          │
│         Store raw payload in OwnerPayout.metadata               │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: MATCH TO RESERVATION (booking_id)                       │
│ Service: PayoutReconciliationService::matchToReservation()      │
│ Action: Lookup PropertyReservation by Airbnb reservation ID      │
│         If not found → create placeholder reservation record     │
│         Validate: reservation.ilan_id exists                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: MATCH TO VILLA (property_id → ilan_id)                  │
│ Action: Get Ilan from reservation.ilan_id                       │
│         Validate: ilan.rental_enabled == true                   │
│         Extract: ilan.cleaning_fee                              │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: MATCH TO OWNER (user_id)                                │
│ Service: PayoutReconciliationService::resolveOwner()            │
│ Action: Get Ilan.ilan_sahibi_id → Kisi                          │
│         Resolve Kisi.user_id (owner account)                    │
│         Validate: owner exists, owner is active                 │
│         ⚠️ TENANT ISOLATION: owner.tenant_id == current tenant  │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 5: CALCULATE COMMISSION, CLEANING FEE, NET PAYOUT          │
│ Service: OwnerPayoutCalculator::calculate()                      │
│ Formula:                                                        │
│   booking_total = airbnb_payout_gross (after Airbnb fees)        │
│   cleaning_fee = Ilan.cleaning_fee (passthrough to owner)        │
│   yalihan_commission = booking_total * 10%                      │
│   owner_net = booking_total - yalihan_commission - cleaning_fee │
│ Rate source: CountryFinancialRule (DB, no hardcode)             │
│ FX: If currency != TRY → convert via FxService                  │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 6: CREATE FINANCIAL RECORD (OwnerPayout)                   │
│ Action: OwnerPayout::create([...all fields])                     │
│         Idempotency: check idempotency_key before insert         │
│         Status: reconciliation_status = 'pending'               │
│         Audit: LogService::action('owner_payout_created', ...)   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 7: RECONCILIATION CHECK                                     │
│ Service: PayoutReconciliationService::reconcile()                │
│ Action: Compare system-calculated owner_net vs Airbnb payout     │
│         If MATCH → reconciliation_status = 'matched'             │
│         If MISMATCH → reconciliation_status = 'mismatch'         │
│         If UNCERTAIN → reconciliation_status = 'pending_review'   │
│                     → Flag for human review                      │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 8: HUMAN APPROVAL THRESHOLD                                 │
│ Rule: If owner_net_payout > APPROVAL_THRESHOLD → approval=pending│
│ Default threshold: 0 (all require approval in pilot)             │
│ Action: Notify approver (Telegram/email)                         │
│         Human reviews in admin panel                             │
│         Approve/Reject → updates approval_status                 │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 9: LEDGER DOUBLE-ENTRY (on approval)                        │
│ Service: FinancialLedgerService::recordDoubleEntry()             │
│ Debit:  Yalıhan Revenue Account (commission income)             │
│ Credit: Owner Payable Account (liability)                        │
│ Amount: yalihan_commission_amount (TRY, locked FX)              │
│         OR owner_net_payout (when actually paid)                 │
│ Reference: OwnerPayout.id                                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 10: OWNER NOTIFICATION                                      │
│ Channel: Telegram / Email (configurable per owner)              │
│ Content: Payout summary — booking dates, gross, commission,      │
│          cleaning fee, net payout, expected payment date        │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Service Architecture (New Services)

```
app/Services/Finance/
  ├── OwnerPayoutCalculator.php       # Pure calculation, no side effects
  ├── PayoutReconciliationService.php # Matching + reconciliation logic
  └── AirbnbPayoutWebhookHandler.php  # Webhook validation + ingestion

app/Http/Controllers/Admin/
  └── OwnerPayoutController.php      # Thin controller (approval UI)

app/Console/Commands/
  └── ProcessAirbnbPayouts.php       # Batch processor for past payouts
```

### 6.3 Reconciliation Logic Detail

| Scenario | System Calculates | Airbnb Reports | Action |
|----------|------------------|---------------|--------|
| Exact match | 9,000 TRY | 9,000 TRY | Auto-approve, `matched` |
| Yalıhan fee difference | 9,000 TRY | 8,800 TRY | Flag `mismatch`, alert finance |
| Currency mismatch | 9,000 TRY (calculated) | 250 USD (reported) | FX convert, re-evaluate |
| Missing cleaning fee | 9,000 - 900 = 8,100 | 8,100 | Warning log, still `matched` |
| Unknown booking | N/A | 9,000 TRY | Create placeholder, `pending_review` |

---

## 7. Pilot Strategy

### 7.1 Pilot Scope

| Dimension | Scope |
|-----------|-------|
| Owner | **Single owner** (Tenant 1, specific `owner_id`) |
| Payment period | **August 2026** (2026-08-01 to 2026-08-31) |
| Channel | **Airbnb only** (no Booking.com, no direct) |
| Bookings | All Airbnb bookings for the pilot owner's villas in August 2026 |
| Validation | Manual spreadsheet calculation = system result (100% match required) |

### 7.2 Pilot Owner Selection Criteria

```
✓ Active tenant (tenant_id = 1)
✓ At least 1 villa listed on Airbnb
✓ At least 3 Airbnb bookings in August 2026
✓ Owner Kisi record exists with user_id link
✓ No financial data in new OwnerPayout table yet
```

### 7.3 Pilot Success Criteria

| # | Criterion | Validation Method |
|---|-----------|------------------|
| 1 | Manual calculation for each booking matches system `owner_net_payout` | Spreadsheet vs `OwnerPayout.owner_net_payout` — 100% match |
| 2 | Airbnb payout gross matches `airbnb_payout_gross` | Bank statement vs system record |
| 3 | Cleaning fee correctly passthrough (not deducted from commission) | Manual verification per booking |
| 4 | Yalıhan 10% commission calculated correctly | `(booking_total - cleaning_fee) * 10%` manual vs system |
| 5 | Tenant isolation: no cross-owner data visible | Query OwnerPayout with wrong tenant_id → empty result |
| 6 | Reconciliation status auto-set to `matched` | All pilot records = `matched` |
| 7 | Approval workflow: pending → approved | Manual approval action in admin UI |

### 7.4 Pilot Data Collection Template

For each Airbnb booking in August 2026, collect:

```
booking_id: ___________
villa_name: ___________
owner_name: ___________
check_in: ___________
check_out: ___________
airbnb_gross_payout: ___________
airbnb_payout_date: ___________
cleaning_fee (from Airbnb): ___________
YSTEM_CALCULATED:
  booking_total: ___________
  cleaning_fee: ___________  ← from Ilan.cleaning_fee
  yalihan_commission (10%): ___________
  owner_net_payout: ___________
MANUAL_CALCULATED:
  booking_total: ___________
  cleaning_fee: ___________
  yalihan_commission (10%): ___________
  owner_net_payout: ___________
MATCH: YES / NO
```

### 7.5 Pilot Timeline

| Week | Activity |
|------|----------|
| Week 1 | Seed `country_financial_rules` with `rental_commission_rate = 0.10` for TR; create `owner_payouts` migration; implement `OwnerPayoutCalculator` |
| Week 2 | Implement `AirbnbPayoutWebhookHandler` + `PayoutReconciliationService`; create admin UI |
| Week 3 | Manual data entry of August 2026 Airbnb bookings for pilot owner; run calculations |
| Week 4 | 100% match validation vs manual spreadsheet; fix discrepancies; approve all |
| Week 5 | Live webhook test; owner notification; ledger double-entry |

---

## 8. EX-002 Charter Draft

```markdown
# EX-002 — Finance Agent Charter
## Airbnb Payout Reconciliation & Owner Net Payout

**Project:** Yalıhan Emlak OS
** Charter Version:** 0.1 (Discovery Draft)
**Author:** Kilo AI Agent (Discovery Phase)
**Status:** DRAFT — Pending Product Owner Approval
**Target Launch:** Sprint EX-002 (TBD)

---

## 8.1 Mission Statement

Automate Airbnb payout reconciliation for Yalıhan Emlak:
- Ingest Airbnb payout data per booking
- Calculate Yalıhan's 10% commission
- Pass through cleaning fee to owner (not retained by Yalıhan)
- Compute owner net payout
- Reconcile expected vs actual payout
- Route to human approval above threshold
- Emit double-entry ledger events
- Notify owner of payout

**Business Impact:** +6% BAI (Bank Ayırlabilir İGelir) through accurate, timely owner payouts and reduced manual reconciliation effort.

---

## 8.2 Scope

### In Scope
- Airbnb payout webhook ingestion
- Single-channel (Airbnb) — extensible later
- Pilot: single owner, August 2026 payment period
- Yalıhan 10% commission calculation
- Cleaning fee pass-through to owner
- Owner net payout calculation
- Reconciliation (matched / mismatch / pending_review)
- Human approval workflow
- Double-entry ledger integration
- Owner Telegram/email notification

### Out of Scope (This Sprint)
- Booking.com / other channel payouts
- Multi-owner batch processing
- Automated owner payment execution (bank transfer)
- Currency conversion beyond TRY (hold EUR/USD as-is)
- Historical data backfill beyond pilot period
- AI optimization of commission rate

---

## 8.3 Deliverables

| # | Deliverable | Owner | Due |
|---|-------------|-------|-----|
| 1 | `owner_payouts` migration + `OwnerPayout` model | EX-002 Agent | Sprint EX-002 |
| 2 | `OwnerPayoutCalculator` service (pure calculation) | EX-002 Agent | Sprint EX-002 |
| 3 | `AirbnbPayoutWebhookHandler` + idempotency | EX-002 Agent | Sprint EX-002 |
| 4 | `PayoutReconciliationService` | EX-002 Agent | Sprint EX-002 |
| 5 | `OwnerPayoutController` + admin views | EX-002 Agent | Sprint EX-002 |
| 6 | `FinansalIslem` tenant_id migration (SAB Rule 1 fix) | EX-002 Agent | Sprint EX-002 |
| 7 | `country_financial_rules` seed for Airbnb (rate = 10%) | EX-002 Agent | Sprint EX-002 |
| 8 | Unit tests (calculator, reconciliation, webhook) | EX-002 Agent | Sprint EX-002 |
| 9 | Pilot validation: 100% match vs manual spreadsheet | Product Owner | Sprint EX-002 |

---

## 8.4 Business Rules (Canonical)

```
1. Yalıhan Commission = (booking_total - cleaning_fee) × 10%
2. Owner Net Payout = booking_total - Yalıhan Commission - cleaning_fee
3. Cleaning fee ALWAYS belongs to owner (not Yalıhan)
4. All financial records MUST have tenant_id (SAB Rule 1)
5. Idempotency required: same Airbnb payout ID = same record (no duplicate)
6. Reconciliation required before any payment action
7. Human approval required for all payouts until threshold is established
8. Double-entry: Yalıhan Revenue DR, Owner Payable CR (on approval)
```

---

## 8.5 Dependencies

| Dependency | Type | Status | Action Required |
|------------|------|--------|-----------------|
| `Ilan.ilan_sahibi_id` → `Kisi` → `User` chain | Data | Partial | Validate pilot owner has full chain |
| `Ilan.cleaning_fee` | Data | Exists | Verify values populated for pilot villas |
| `CountryFinancialRule` for TR | Config | Exists | Seed `rental_commission_rate = 0.10` |
| `PropertyReservation.booking_fx_rate` | Data | Exists | Verify Airbnb bookings have FX rate |
| `FinancialLedgerService` | Service | Exists | Use as-is for double-entry |
| `FinansalIslem.tenant_id` | Migration | Missing | Add before EX-002 goes live |
| Airbnb payout webhook endpoint | Infrastructure | Missing | Create new webhook route |

---

## 8.6 Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Owner chain `Kisi → User` broken for pilot owner | Medium | High | Pre-flight data check before sprint start |
| `Ilan.cleaning_fee` values are 0 or null | Medium | Medium | Default to 0, alert if unexpected |
| Airbnb payout webhook not available/proxied | Low | High | Manual CSV import fallback |
| `FinansalIslem` tenant_id gap causes cross-tenant leak | Medium | Critical | Fix migration first, before any new finance work |
| Commission rate changes mid-sprint | Low | Medium | Use `CountryFinancialRule` — no hardcode |
| Currency conversion discrepancy | Medium | Medium | Lock FX rate at payout time; compare within 1% tolerance |

---

## 8.7 Success Metrics

| Metric | Baseline | Target | Measurement |
|--------|----------|--------|-------------|
| Pilot: Manual = System match rate | N/A | **100%** | Per-booking comparison |
| Reconciliation auto-match rate | 0% | **>90%** | `reconciliation_status = 'matched'` |
| Time to reconcile single payout | Manual (60 min) | **<5 min** | System calculation vs manual |
| Cross-tenant data leaks | 1 known (`FinansalIslem`) | **0** | SAB integrity scan |
| BAI improvement | Baseline | **+6%** | Quarterly owner payout accuracy |

---

## 8.8 Pilot Success Criteria (Updated per SAAB Review)

| # | Kanıt | Beklenti |
|---|-------|----------|
| 1 | AirbnbPayoutImport | Tüm Airbnb payout verileri eksiksiz import edildi |
| 2 | Reservation matching | %100 veya tüm unmatched kayıtlar açıkça belgelendi |
| 3 | Komisyon hesabı | Manuel hesapla birebir (komisyon tabanı iş kuralına bağlı) |
| 4 | Temizlik ücreti | İş kuralıyla uyumlu işlendi |
| 5 | Owner net payout | Manuel hesapla birebir |
| 6 | Duplicate import | 0 (idempotency çalıştı) |
| 7 | Cross-tenant erişim | 0 |
| 8 | Human approval | Tüm payout'lar ödeme öncesi onaylandı |
| 9 | Audit trail | Baştan sona izlenebilir (AirbnbPayoutImport → OwnerPayout) |

---

## 8.9 Pilot Mandatory Output Table

**Scope:** Tek ev sahibi, Ağustos 2026, Airbnb only

| Alan | Zorunlu | Açıklama |
|------|---------|-----------|
| Konaklama geliri | ✅ | `accommodation_revenue` |
| Temizlik ücreti | ✅ | Airbnb payout'tan gelen gerçek değer |
| Airbnb kesintileri | ✅ | `airbnb_host_service_fee`, `taxes_withheld`, `adjustments` |
| Refund/Adjustment | ✅ | `refunds`, `owner_chargeable_adjustments` |
| Komisyon tabanı | ✅ | `gross_booking_amount = accommodation + cleaning + other` |
| Yalıhan komisyonu (%10) | ✅ | `gross_booking_amount × 10%` |
| Ev sahibi neti | ✅ | Sistem sonucu manuel hesapla %100 eşleşmeli |
| Airbnb payout ile mutabakat | ✅ | `actual_airbnb_payout` reconciliation |
| İnsan onayı | ✅ | Tüm payout'lar ödeme öncesi onaylanmalı |
| Audit trail | ✅ | AirbnbPayoutImport → OwnerPayout baştan sona izlenebilir |

---

## 8.10 Out of Scope Decisions (Deferred)

1. **Automated bank transfer execution** — requires separate payment gateway sprint
2. **Booking.com integration** — same pattern, separate channel adapter
3. **Commission rate optimization via AI** — future AI Workforce sprint
4. **Multi-currency owner wallets** — hold all in TRY for now
5. **Historical backfill** — pilot only, no legacy data
6. **Real-time payout dashboard** — post-pilot analytics sprint

---

---

## 9. Final Business Rules (Product Owner — Resolved 2026-08-07)

> **Status:** ✅ ALL DECISIONS RESOLVED — Implementation authorized

### Rule 1: Komisyon Tabanı — APPROVED

**Decision:** Temizlik dahil toplam rezervasyon geliri üzerinden %10.

```
commission_base = accommodation_revenue + cleaning_fee + other_guest_charges
yalihan_commission = commission_base × 10%
```

**İstisna:** İleride `CommissionPolicy` property/owner bazlı tanımlanabilir. İlk pilotta sabit %10 uygulanır.

---

### Rule 2: Temizlik Ücreti — APPROVED

**Decision:** Airbnb rezervasyon/payout kaydındaki gerçek temizlik ücreti esas alınır. `Ilan.cleaning_fee` sadece reconciliation fallback olarak kullanılır.

**Öncelik sırası:**
```
1. Airbnb payout/reservation cleaning_fee
2. yoksa → Airbnb reservation breakdown
3. yoksa → Ilan.cleaning_fee (FALLBACK ONLY)
4. fallback kullanıldıysa → insan onayı zorunlu
```

Temizlik ücreti ev sahibine aittir; ayrıca Yalıhan komisyon tabanına dahildir.

---

### Rule 3: Airbnb Kesintileri — APPROVED

**Decision:** Airbnb host payout'tan düşürülen platform bedelleri ev sahibinin finansal sonucuna aittir.

**Muhasebe alanları:**
```
gross_booking_amount         # Airbnb'den alınan toplam
airbnb_host_service_fee      # Airbnb platform kesintisi
refunds                      # İadeler
taxes_withheld               # Vergiler
adjustments                  # Düzenlemeler
actual_airbnb_payout         # Airbnb'nin gönderdiği net tutar
```

**Formül:**
```
gross_booking_amount
- airbnb_host_service_fee
- refunds
- owner_chargeable_adjustments
- yalihan_commission
= owner_net_payout
```

**Kural:** Belirsiz adjustment varsa → reconciliation exception oluşturulur, otomatik karar VERİLMEZ. Yalıhan'ın kendi hatası/üstlendiği gider owner'a yansıtılmaz.

---

### Rule 4: FinansalIslem V1/V2 — APPROVED

**Decision:** EX-002, `FinansalIslem` modelini read/write path'inde KULLANMAYACAK.

**Sınıflandırma:**
```
FinansalIslem V1 Tenant Remediation
Status: OPEN CERTIFICATION / ARCHITECTURE DEBT
Scope: Separate remediation sprint
EX-002 Dependency: NONE
```

Yeni Finance Agent tamamen V2-only zincir üzerine kurulur:
```
AirbnbPayoutImport → PayoutReconciliation → OwnerPayout
```

---

### Final Canonical Formulas

**İlk pilot (Ağustos 2026):**
```
gross_booking_amount = accommodation_revenue + cleaning_fee + other_guest_charges
yalihan_commission = gross_booking_amount × 10%
owner_net_payout = gross_booking_amount
                  - airbnb_host_service_fee
                  - refunds
                  - owner_chargeable_adjustments
                  - yalihan_commission
```

**Kontrol eşitliği:**
```
actual_airbnb_payout - yalihan_commission - post_payout_owner_adjustments = payable_to_owner
```

---

*Document Status: 🟢 AUTHORIZED — All decisions resolved (v1.0)*
*Decisions resolved: 2026-08-07 by Product Owner*
*Implementation: Authorized — Use Claude Sonnet 4.6 for Laravel implementation*
*Next Step: Product Owner approves Charter → Sprint EX-002 begins*
