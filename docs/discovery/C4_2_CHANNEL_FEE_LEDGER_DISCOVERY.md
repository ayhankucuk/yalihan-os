# C4.2 — Channel Fee Double-Entry Ledger Accrual Discovery Report

**Document ID:** `C4_2_CHANNEL_FEE_LEDGER_DISCOVERY`  
**Certified Production Code Baseline:** `1a2e9cec7fdb5026a27e7d6928e469cba35832a8`  
**Prerequisites:** `C1_C3_PRODUCTION_CERTIFICATION_PASS`, `C4_1_CERTIFICATION_PASS`, `C4_1_PRODUCTION_DEPLOYMENT_PASS`  
**Role:** Finance Architecture / Discovery Agent  
**Mode:** READ-ONLY DISCOVERY  
**Date:** 2026-08-23  

---

## 1. Executive Summary

C4.1 certified and deployed the **Channel Fee Snapshot & Policy Foundation**, establishing:
1. 7 immutable snapshot fields on `property_reservations`.
2. The locked `OWNER_BORNE` policy gate.
3. Strict blocking of payout readiness when channel fee data is `UNKNOWN` or unverified.

The goal of **C4.2** is to transition from purely calculating payout readiness to **actively recording verified channel fee accruals in the double-entry general ledger (`FinancialLedgerService`)**.

Target lifecycle:
$$\text{ReservationCompletedEvent} \longrightarrow \text{ProcessFinancialCompletionJob} \longrightarrow \text{Channel Fee Accrual (TX1)} \longrightarrow \text{Yalıhan Commission Accrual (TX2)} \longrightarrow \text{Owner Payable Accrual (TX3)} \longrightarrow \text{Payout Readiness}$$

---

## 2. Gate 1 — FinancialLedgerService Architecture Audit

Inspection of [`App\Services\FinancialLedgerService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/FinancialLedgerService.php):
- **Core Function:** `recordDoubleEntry($debitAccount, $creditAccount, $amount, $currency, ...)`
  - Wrapped in `DB::transaction()`.
  - Account row-locking with `LedgerAccount::whereIn('id', $accountIds)->lockForUpdate()`.
  - Atomic double entries in `ledger_entries` (1 debit row, 1 credit row) under a shared `transaction_group_id`.
  - Idempotency guard via `ledger_transactions.idempotency_key`.
- **Existing Rental Lifecycle Methods:**
  - `recordReservationInitialBooking()`: Debits `Misafir Alacakları` vs Credits `Konaklama / Kira Gelirleri`.
  - `recordOwnerPayableAccrual()`: Currently splits gross into `Komisyon Gelirleri` (TX2) and `Sahip Yükümlülükleri` (TX3).
  - `reverseOwnerPayableAccrual()`: Reverses TX2 and TX3 upon cancellation.
  - `recordReservationCancellation()`: Reverses initial booking (Debits `Konaklama / Kira Gelirleri` vs Credits `Misafir Alacakları`).

---

## 3. Gate 2 — Ledger Account Plan Audit

Inspection of canonical accounts in [`App\Models\LedgerAccount`](file:///Users/macbookpro/repos/yalihan-os/app/Models/LedgerAccount.php):

| Hesap Adı | Hesap Tipi | Standart No / Display Order | Rolü |
|---|---|---|---|
| **Misafir Alacakları Hesabı** | `aktif` (Asset) | 10 | Rezervasyon brüt alacağı |
| **Konaklama / Kira Gelirleri** | `gelir` (Revenue) | 20 | Brüt konaklama havuzu |
| **Komisyon Gelirleri Hesabı** | `gelir` (Revenue) | 30 | Yalıhan yönetim komisyon geliri |
| **Sahip Yükümlülükleri Hesabı** | `yükümlülük` (Liability) | 40 | Ev sahibine ödenecek hakediş borcu |
| **Kanal Komisyon Yükümlülükleri Hesabı** *(Önerilen C4.2)* | `yükümlülük` (Liability / Clearing) | 50 | OTA platformuna/aracıya ait kesinti yükümlülüğü |

---

## 4. Gate 3 & 4 — Çift Taraflı Muhasebe Semantiği (Canonical Journal Entries)

### Kural (OWNER_BORNE Policy):
$$\text{Brüt Tutar (100.000 TL)} = \text{Kanal Kesintisi (15.500 TL)} + \text{Yalıhan Komisyonu (15.000 TL)} + \text{Ev Sahibi Hakedişi (69.500 TL)}$$

### Yevmiye Kayıtları (Journal Entries upon Completion):

1. **Rezervasyon Girişi (Initial Booking):**
   - **Borç (Debit):** `Misafir Alacakları Hesabı` $\rightarrow$ **100.000 TL**
   - **Alacak (Credit):** `Konaklama / Kira Gelirleri` $\rightarrow$ **100.000 TL**

2. **Tamamlanma Tahakkukları (`recordOwnerPayableAccrual` genişletmesi):**
   - **TX1 (Kanal Kesintisi Tahakkuku):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` $\rightarrow$ **15.500 TL**
     - **Alacak (Credit):** `Kanal Komisyon Yükümlülükleri Hesabı` $\rightarrow$ **15.500 TL**
     - *Idempotency Key:* `"owner_accrual_{$reservation->id}_{$tenantId}_channel_fee"`
   - **TX2 (Yalıhan Yönetim Komisyonu):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` $\rightarrow$ **15.000 TL**
     - **Alacak (Credit):** `Komisyon Gelirleri Hesabı` $\rightarrow$ **15.000 TL**
     - *Idempotency Key:* `"owner_accrual_{$reservation->id}_{$tenantId}_commission"`
   - **TX3 (Ev Sahibi Net Hakedişi):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` $\rightarrow$ **69.500 TL**
     - **Alacak (Credit):** `Sahip Yükümlülükleri Hesabı` $\rightarrow$ **69.500 TL**
     - *Idempotency Key:* `"owner_accrual_{$reservation->id}_{$tenantId}_owner"`

### Defter Dengesi & Matematiksel Kanıt (Ledger Invariant):
- `Konaklama / Kira Gelirleri` Alacak Toplamı: $+100.000$ TL
- `Konaklama / Kira Gelirleri` Borç Toplamı: $-15.500 - 15.000 - 69.500 = -100.000$ TL
- **Konaklama Gelirleri Net Bakiye: 0.00 TL (Tam Denge ✅)**
- Toplam Borç = Toplam Alacak = **100.000 TL** ($\sum \text{Debit} = \sum \text{Credit} \checkmark$).

---

## 5. Gate 5 — Veri Güvenilirliği & Tahakkuk Sınırı (Source Trust Gate)

`recordOwnerPayableAccrual()` içinde:
1. Eğer `channel_fee_bearer === OWNER_BORNE`:
   - `channel_fee_amount` doğrulanmış mı? (`channel_fee_is_verified === true` VEYA `channel_fee_source === PROVIDER_REPORTED`).
   - Eğer `channel_fee_source === UNKNOWN` veya `channel_fee_amount === null`:
     - **Tahakkuk yapılmaz (NO ACCRUAL)**.
     - Güvenli audit log atılır.
     - Payout readiness bloke kalır.
2. Eğer `channel_fee_bearer === YALIHAN_BORNE`:
   - Kanal komisyonu Yalıhan'ın kendi payından düşüleceği için ev sahibi tahakkuku $\text{Gross} - \text{Yalıhan Komisyonu}$ olarak tam yazılır; kanal ücreti Yalıhan gideri olarak kaydedilir.
3. Eğer `external_channel === direct` (veya kanal ücreti $0.00$):
   - TX1 atlanır ($0.00$ tutarlı işlem kaydedilmez), sadece TX2 ve TX3 işletilir.

---

## 6. Gate 6 & 7 — Transaction Sınırı, Deadlock & Replay Koruması

- **Tek Atomik Transaction:** TX1, TX2 ve TX3 `FinancialLedgerService::recordOwnerPayableAccrual` içinde tek bir `DB::transaction` ile çevrelenir. Kısmi state oluşamaz.
- **Idempotency Key:** Her bir alt işlem için `owner_accrual_{res_id}_{tenant}_channel_fee`, `_commission`, `_owner` anahtarları kullanılır.
- **Replay Davranışı:** `ProcessFinancialCompletionJob` ikinci kez çalıştığında, `ledger_transactions` tablosundaki mevcut anahtarlar nedeniyle sıfır mükerrer satır üretilir (`no-op`).

---

## 7. Gate 8 — İptal ve Ters Kayıt (Cancellation Reversal)

Tarihsel muhasebe satırları asla `UPDATE` veya `DELETE` edilmez. İptal durumunda `reverseOwnerPayableAccrual()` simetrik ters kayıtlar üretir:
- **Ters TX1:** Borç `Kanal Komisyon Yükümlülükleri` $\rightarrow$ Alacak `Konaklama / Kira Gelirleri` (15.500 TL)
- **Ters TX2:** Borç `Komisyon Gelirleri` $\rightarrow$ Alacak `Konaklama / Kira Gelirleri` (15.000 TL)
- **Ters TX3:** Borç `Sahip Yükümlülükleri` $\rightarrow$ Alacak `Konaklama / Kira Gelirleri` (69.500 TL)
- **Ters Ana Kayıt:** Borç `Konaklama / Kira Gelirleri` $\rightarrow$ Alacak `Misafir Alacakları` (100.000 TL)

Tüm hesaplar net $0.00$ TL bakiyeye döner.

---

## 8. Gate 9 & 10 — Tenant İzolasyonu & Snapshot Değişmezliği

- **Tenant Scoping:** `LedgerAccount` ve `LedgerEntry` tablolarındaki tüm işlemler `tenant_id` filtresi ile sınırlıdır.
- **Immutability:** Finansal tamamlanma gerçekleştikten sonra rezervasyon üzerindeki `channel_fee_*` snapshot alanları re-run veya konfigürasyon değişikliklerinde güncellenmez.

---

## 9. Gate 11 — PayoutReadiness Entegrasyonu

`PayoutReadinessService` hakedişi `READY_FOR_PAYOUT` olarak işaretlemek için:
1. `channel_fee_bearer === OWNER_BORNE` ise:
   - `channel_fee_amount` doğrulanmış olmalı.
   - `Kanal Komisyon Yükümlülükleri Hesabı` ve `Sahip Yükümlülükleri Hesabı` için defter kaydı (`LedgerEntry`) oluşmuş olmalı (`has_channel_fee_ledger_entry === true`).

---

## 10. Discovery Verdict

$$\mathbf{Verdict: \quad C4\_2\_CHARTER\_READY}$$
