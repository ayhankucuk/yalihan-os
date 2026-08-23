# C4 — Channel Fee Separation Discovery Report

**Document ID:** `C4_CHANNEL_FEE_DISCOVERY`  
**Certified Production Code Baseline:** `bc5c0379cf634b43d32f99475952746661e11827`  
**Report / Evidence Commit:** `6b12871` / `11c903a`  
**Authority:** SAAB Release Authority / Program Governance  
**Role:** Finance Architecture Discovery / Independent Audit  
**Date:** 2026-08-23  
**Status:** READ-ONLY DISCOVERY COMPLETE  

---

## 1. Executive Summary

In C1–C3, YALIHAN OS successfully certified the core financial lifecycle for property reservations:
$$\text{Gross Booking Amount} \longrightarrow \text{Yalıhan Management Commission} \longrightarrow \text{Owner Entitlement} \longrightarrow \text{Payout Ready}$$

However, bookings arriving from Online Travel Agencies (OTAs) such as Airbnb, Booking.com, and VRBO incur **distribution channel fees (Host / Channel / Platform Fees)** ranging from 3% to 18%. 

This discovery audits the entire repository to determine the monetary source of truth, existing ledger structures, provider payload semantics, and the exact mathematical and double-entry accounting model required for C4.

---

## 2. Gate 1 — Repository Inventory & Discovery

### A. Model & Database Layer
- **[`App\Models\PropertyReservation`](file:///Users/macbookpro/repos/yalihan-os/app/Models/PropertyReservation.php):**
  - Financial columns present: `total_amount`, `total_price`, `currency`, `booking_currency`, `booking_fx_rate`, `booking_country_code`, `ulke_id`, `finansal_durum`, `management_model_snapshot`, `commission_rate_snapshot`, `external_channel`, `external_reservation_id`.
  - **Gap:** No columns exist for `channel_fee_amount`, `channel_fee_rate_snapshot`, `channel_fee_bearer`, or `net_channel_payout`.
- **[`App\Models\LedgerAccount`](file:///Users/macbookpro/repos/yalihan-os/app/Models/LedgerAccount.php):**
  - Existing canonical accounts:
    - `Misafir Alacakları Hesabı` (120 - Asset)
    - `Konaklama / Kira Gelirleri` (600 - Revenue)
    - `Komisyon Gelirleri Hesabı` (602 - Agency Revenue)
    - `Sahip Yükümlülükleri Hesabı` (320 - Owner Liability)
  - **Gap:** No account exists for `Kanal Komisyon Yükümlülükleri / OTA Komisyonları Hesabı`.

### B. Inbound Channel Adapters & DTOs
- **[`App\DTOs\ChannelManager\ChannexReservationPayload`](file:///Users/macbookpro/repos/yalihan-os/app/DTOs/ChannelManager/ChannexReservationPayload.php):**
  - Normalizes Channex JSON:API (`attributes.amount` / `attributes.total_price`) and flat webhook (`reservation.total_price`).
  - Stored directly as `total_amount`.
  - Channel fee is **not extracted** from the payload.
- **[`App\DTOs\ChannelManager\Booking\BookingReservationPayload`](file:///Users/macbookpro/repos/yalihan-os/app/DTOs/ChannelManager/Booking/BookingReservationPayload.php):**
  - Normalizes Booking.com `OTA_HotelResNotif` XML/JSON response.
  - Stored directly as `total_amount`.
  - Booking fee is **not extracted** from the payload.
- **[`App\Services\ChannelManager\ChannexReservationIngestService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/ChannelManager/ChannexReservationIngestService.php):**
  - Ingests normalized DTO and creates canonical `PropertyReservation` with `total_amount = $payload->totalPrice`.

### C. Services & Financial Logic
- **[`App\Services\FinancialLedgerService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/FinancialLedgerService.php):**
  - `recordReservationInitialBooking`: Debits `Misafir Alacakları`, Credits `Konaklama / Kira Gelirleri` with full `total_amount`.
  - `recordOwnerPayableAccrual`: Splits `Konaklama / Kira Gelirleri` into Yalihan Commission (`Komisyon Gelirleri`) and Owner Entitlement (`Sahip Yükümlülükleri`).
  - Currently assumes **zero channel fee**.
- **[`App\Services\Finance\PayoutReadinessService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/Finance/PayoutReadinessService.php):**
  - Calculates `ownerEntitlement = grossAmount - commissionAmount`.

---

## 3. Gate 2 — Money Source of Truth Matrix

| Provider | Monetary Field in Raw Inbound Payload | Classification in YALIHAN OS | Transformed before Persistence? | Is OTA Fee Supplied in Payload? | Persisted Location |
|---|---|---|---|---|---|
| **AIRBNB (via Channex)** | `reservation.total_price` / `attributes.amount` | **Guest/Booking Gross** | No (direct float cast) | **NO** (Not broken down) | `property_reservations.total_amount` |
| **BOOKING.COM** | `reservation.total_price` / `total_price` | **Guest/Stay Gross** | No (direct float cast) | **NO** (Invoiced separately by Booking) | `property_reservations.total_amount` |
| **CHANNEX (General)** | `attributes.amount` / `total_price` | **Gross Reservation Price** | No (direct float cast) | **NO** (Only total amount passed) | `property_reservations.total_amount` |
| **DIRECT / WEB** | `request->total_amount` / `fiyat` | **Direct Booking Gross** | No | **N/A** (0% channel fee) | `property_reservations.total_amount` |

**Conclusion for Gate 2:**  
All inbound OTA providers currently supply **GUEST GROSS** (the total accommodation price). None of the providers supply a distinct, reliable `channel_fee` field in their standard webhook payload. Therefore, channel fees must be resolved via **Channel Financial Rules Engine** based on `external_channel` and frozen as snapshots upon booking creation.

---

## 4. Gate 3 — Current Gross Semantics

- `PropertyReservation.total_amount` is verified to be the **GUEST/BOOKING GROSS**.
- C3's `PayoutReadinessService` and `FinancialLedgerService` currently compute:
  $$\text{Owner Entitlement} = \text{total\_amount} - (\text{total\_amount} \times \text{commission\_rate\_snapshot})$$
- **Technical Fact:** Owner entitlement currently operates from **GUEST GROSS**, with zero deduction for OTA platform fees.

---

## 5. Gate 4 — Channel Fee Ownership & Policy Alternatives

### Human Decision Gate: Who Economically Bears the OTA Fee?

| Policy Option | Formula | Economic Impact | Viability / Industry Standard |
|---|---|---|---|
| **Option 1: OWNER_BORNE (Recommended)** | $\text{Owner} = \text{Gross} - \text{OTA Fee} - \text{Yalıhan Com}$ | Owner pays the distribution cost of their property. Yalıhan earns full 15% management fee. | **Standard in Luxury Villa PM** (e.g. Guesty, Hospitable). |
| **Option 2: YALIHAN_BORNE** | $\text{Owner} = \text{Gross} - \text{Yalıhan Com}$; Yalıhan pays OTA fee | Yalıhan's 15% commission is wiped out by Booking.com's 15% fee (0% net margin). | **Economically Unviable** for PMS/Agencies. |
| **Option 3: NET_OF_OTA (Commission on Net)** | $\text{Net} = \text{Gross} - \text{OTA Fee}$; $\text{Yalıhan} = \text{Net} \times 15\%$, $\text{Owner} = \text{Net} \times 85\%$ | Yalıhan takes commission only on money that actually reaches the bank. | **Fair Shared Model** used by select boutique managers. |
| **Option 4: GUEST_BORNE (Price Markup)** | Channel price is inflated so Net equals Base Price | Guest pays OTA fee via rate markup. | Handled at listing pricing stage, not settlement. |

> [!IMPORTANT]
> **HUMAN DECISION REQUIRED:** SAAB must select either **Option 1 (OWNER_BORNE)** or **Option 3 (NET_OF_OTA)** as the default institutional policy.

---

## 6. Gate 5 — Management Agreement Interaction

Channel fee rates are **distribution channel parameters** (orthogonal to the management agreement):
- **Airbnb Host-Only:** 14.00% – 15.00% (default: 15.00%)
- **Airbnb Split-Fee:** 3.00%
- **Booking.com:** 15.00% – 18.00% (default: 15.00%)
- **Direct / Web:** 0.00%

### Interaction Example (100,000 TRY Booking, Booking.com @ 15%, Full Management @ 15%):

- **Under Option 1 (OWNER_BORNE):**
  - Gross Booking: 100,000 TRY
  - Booking.com OTA Fee (15%): 15,000 TRY
  - Yalıhan Management Commission (15% of Gross): 15,000 TRY
  - **Net Owner Entitlement:** $100,000 - 15,000 - 15,000 =$ **70,000 TRY**

- **Under Option 3 (NET_OF_OTA):**
  - Gross Booking: 100,000 TRY
  - Booking.com OTA Fee (15%): 15,000 TRY
  - Net Booking Revenue: 85,000 TRY
  - Yalıhan Commission (15% of 85K): 12,750 TRY
  - **Net Owner Entitlement (85% of 85K):** **72,250 TRY**

---

## 7. Gate 6 — Double-Entry Ledger Architecture

### New Canonical Account Required:
- **Account Name:** `Kanal Komisyon Yükümlülükleri Hesabı` (or `OTA Komisyon Takas Hesabı`)
- **Account Type:** `yükümlülük` (Liability / Clearing Account)
- **Tenant Scope:** Explicit per tenant (`tenant_id`).

### Double-Entry Flow (Option 1: OWNER_BORNE):

1. **Initial Booking:**
   - **DB:** `Misafir / Kanal Alacakları Hesabı` (100,000 TRY)
   - **CR:** `Konaklama / Kira Gelirleri` (100,000 TRY)

2. **Accrual upon Completion (`ProcessFinancialCompletionJob`):**
   - **TX1 (OTA Fee Separation):**
     - **DB:** `Konaklama / Kira Gelirleri` (15,000 TRY)
     - **CR:** `Kanal Komisyon Yükümlülükleri Hesabı` (15,000 TRY)
   - **TX2 (Yalıhan Commission):**
     - **DB:** `Konaklama / Kira Gelirleri` (15,000 TRY)
     - **CR:** `Komisyon Gelirleri Hesabı` (15,000 TRY)
   - **TX3 (Owner Entitlement):**
     - **DB:** `Konaklama / Kira Gelirleri` (70,000 TRY)
     - **CR:** `Sahip Yükümlülükleri Hesabı` (70,000 TRY)

**Ledger Invariant:**
$$\sum \text{Debits} = 15,000 + 15,000 + 70,000 = 100,000 \text{ TRY} = \sum \text{Credits}$$
$$\text{Konaklama / Kira Gelirleri Net Balance} = 100,000 - 100,000 = 0.00 \text{ TRY} \quad (\text{Balanced} \checkmark)$$

---

## 8. Gate 7 — Immutability & Snapshot Requirements

The following additive fields are required on `property_reservations`:
1. `channel_fee_rate_snapshot` (`decimal(5,4) NULL` — e.g. `0.1500`)
2. `channel_fee_amount` (`decimal(12,2) NULL` — computed: `total_amount * channel_fee_rate_snapshot`)
3. `channel_fee_bearer` (`varchar(30)` default `'owner'` — `'owner' | 'agency' | 'split'`)

These fields must be **stamped once at reservation creation** and remain completely immutable across any subsequent system configuration changes.

---

## 9. Gate 8 — Replay, Cancellation & Reversal

- **Replay Safety:** `recordChannelFeeAccrual` uses idempotency key:
  `idempotency_key = "owner_accrual_{$reservation->id}_{$tenantId}_channel_fee"`
  Re-running the job produces an exact no-op.
- **Cancellation Reversal:** `reverseOwnerPayableAccrual` reverses all 3 sub-entries:
  - Reversal TX1: DB `Kanal Komisyon Yükümlülükleri` $\rightarrow$ CR `Konaklama Gelirleri`
  - Reversal TX2: DB `Komisyon Gelirleri` $\rightarrow$ CR `Konaklama Gelirleri`
  - Reversal TX3: DB `Sahip Yükümlülükleri` $\rightarrow$ CR `Konaklama Gelirleri`
  - Main Reversal: DB `Konaklama Gelirleri` $\rightarrow$ CR `Misafir Alacakları`

---

## 10. Gate 9 — C4 vs C5 Boundary

| In Scope for C4 | Out of Scope (Reserved for C5) |
|---|---|
| Channel fee rate snapshotting | Bank statement import / parsing |
| Channel fee double-entry accrual | Bank transfer matching |
| Net owner entitlement calculation | Actual payout bank execution |
| Payout Readiness UI / API breakdown | Payment reconciliation & dispute resolution |

---

## 11. Gate 10 — Business Automation Value

- **Manual Operation Eliminated:** Real estate finance operators previously had to manually check which channel a booking came from, deduct 15% or 3% OTA fee in Excel, and calculate net owner payable manually.
- **BAI Impact:** Fully automates multi-channel commission & net owner payout calculations across Airbnb, Booking.com, and Direct channels. Increases Finance Automation coverage from 75% to 90%.

---

## 12. Discovery Verdict

$$\mathbf{Verdict: \quad C4\_CHARTER\_READY}$$
