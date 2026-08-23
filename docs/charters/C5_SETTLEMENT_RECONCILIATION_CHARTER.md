# C5 — Settlement & Reconciliation Implementation Charter

**Charter ID:** `C5_SETTLEMENT_RECONCILIATION_CHARTER`  
**Certified Production Code Baseline:** `35b4e6c0d1b08bb2fd0dcfd01527ebfeda088547`  
**Discovery Reference:** `docs/discovery/C5_SETTLEMENT_RECONCILIATION_DISCOVERY.md`  
**Target Milestone:** ERA-V Phase 2A — Monetization Core (Settlement Engine)  
**Role:** Finance Architecture & Governance  
**Status:** PROPOSED / AWAITING SAAB DECISION GATE  

---

## 1. Hedef ve Temel İlke (Objective & Core Invariant)

### Temel İşletme İlkesi (Core Invariant)
> **"YALIHAN OS hiçbir zaman 'OTA gönderdi' bilgisini 'bankaya para geldi' olarak kabul etmez. Payout release ancak provider settlement ile gerçek cash movement güvenli biçimde eşleştiğinde ilerler."**

### Hedef
Kanal rezervasyon tahakkuku (`C4.2 Accrual`) ile gerçek banka nakit akışı (`Bank Cash Movement`) arasındaki boşluğu kapatmak; OTA payout verileri ile banka hesap hareketlerini eşleştirerek ev sahibi ödeme kilidini (`Payout Release Gate`) güvenli ve denetlenebilir biçimde açmak.

---

## 2. Dörtlü Veri Modeli ve Domain Sınırları (4 Core Aggregates)

```
┌─────────────────────────┐       ┌───────────────────────────┐
│   ProviderSettlement    │       │      BankTransaction      │
│ (OTA Payout Batch Olayı)│       │  (Gerçek Banka Hareketi)  │
└───────────┬─────────────┘       └─────────────┬─────────────┘
            │ 1:N                               │ 1:N
            ▼                                   │
┌─────────────────────────┐                     │
│  SettlementAllocation   │                     │
│ (Rezervasyon Dağılımı)  │                     │
└───────────┬─────────────┘                     │
            │                                   │
            └───────────────┐   ┌───────────────┘
                            ▼   ▼
             ┌──────────────────────────────────┐
             │     ReconciliationExecution      │
             │   (Immutable Denetim & Eşleşme)  │
             └──────────────────────────────────┘
```

1. **`ProviderSettlement` (Aggregate 1):** OTA'nın "şu batch ID ile şu kadar para gönderdim" kaydı (Batch ID, kanal adı, brüt, toplam kanal komisyonu, net transfer tutarı, valör).
2. **`SettlementAllocation` (Aggregate 2):** Toplu payout içindeki rezervasyon bazlı satır dökümü (Rezervasyon ID, brüt tutar, kesilen komisyon, net dağılım payı).
3. **`BankTransaction` (Aggregate 3):** Banka ekstresinden (Open Banking API / MT940 / CSV) gelen gerçek nakit hareketi (Banka ID, dekont no, işlem tarihi, valör, net yatan tutar, para birimi, açıklama metni).
4. **`ReconciliationExecution` (Aggregate 4):** İki tarafı eşleştiren **immutable audit kaydı** (Eşleşen tutar, uyuşmazlık farkı $\Delta$, tolerans kararı, onaylayan operatör/sistem, çalışma zamanı damgası).

---

## 3. Settlement Modaliteleri ve Çift Kesinti Önleme Invariant'ı

### Modalite Ayrımı (Settlement Modalities)
Farklı kanalların ödeme yapıları tek tip kabul edilemez:
- **`BT_NET` (Bank Transfer - Net Payout):** Kanal komisyonunu önceden keserek bankaya sadece net tutarı gönderir (Airbnb Direct Payout, Booking.com Bank Transfer Net).
- **`BT_GROSS` (Bank Transfer - Gross Payout):** Kanal brüt tutarı bankaya gönderir; komisyon faturasını ay sonunda ayrı tahsil eder.
- **`VCC` (Virtual Credit Card):** Rezervasyon bazlı sanal kart çekimi. Genellikle brüt tutardır; POS/banka komisyonu anlık kesilir.

### Çift Kesinti Önleme Invariant'ı (Double-Deduction Prevention Invariant)
$$\mathbf{Invariant:} \quad \text{C4'te tahakkuk eden } \mathtt{channel\_fee\_amount}, \text{ C5 settlement sürecinde ASLA mükerrer düşülemez.}$$
- `BT_NET` durumunda bankaya giren para zaten net olduğundan, settlement defter kaydı $120 \text{ Alacak}$ ile $329 \text{ Kanal Yükümlülüğü}$'nü mahsup eder; ev sahibi alacağından ikinci kez kanal ücreti eksiltilmez.
- `BT_GROSS` veya `VCC` durumunda modalite snapshot'ı beklenen banka tutarını brüt olarak modeller.

---

## 4. Tolerans Politikası ve Karar Kapısı (Tolerance Governance)

1. **Kanonik Durum:**
   $$\mathtt{reconciliation\_tolerance} = \mathbf{POLICY\_UNDECIDED}$$
   Otomatik bir kuruş/lira toleransı peşinen kabul edilmez.
2. **Uyuşmazlık Çözümü (Human Decision Gate):**
   - $\Delta = 0$ (Tam Eşleşme): Sistem otomatik `RECONCILED` statüsüne geçirir.
   - $\Delta \ne 0$ (Fark / Uyuşmazlık): Sistem otomatik gider kaydı **atamaz**. İşlem `DISCREPANCY_HELD` statüsünde kalır ve yetkili operatör/SAAB onayına sunulur.

---

## 5. Durum Ayrımı ve Yaşam Döngüsü (State Machine Decoupling)

İki kritik durum kesin olarak birbirinden ayrılmıştır:

```
[ AWAITING_SETTLEMENT ]
          │
          ▼  (Provider Payout + Bank Match Verified)
    [ RECONCILED ]  ──► (Sahip hakedişi güvenle ödenebilir duruma gelir)
          │
          ▼  (Banka Emri & Para Çıkışı Gerçekleştiğinde)
  [ PAYOUT_SETTLED / PAID ]
```

- **`RECONCILED`:** Gelen paranın doğrulanması (Inbound Cash Verification).
- **`PAYOUT_SETTLED`:** Ev sahibine paranın transfer edilmesi (Outbound Cash Disbursement).

---

## 6. İptal, İade & Chargeback Alt Yaşam Döngüsü (Recovery Sub-Lifecycle)

Konaklama sonrası ortaya çıkan chargeback, tazminat veya iade kesintileri ana mutabakat akışından bağımsız bir alt yaşam döngüsüyle yönetilir:
- **Henüz Ödenmemiş Hakediş:** `Sahip Yükümlülükleri` revize edilir, hakediş düşürülür.
- **Ödenmiş Hakediş:** Ev sahibi bakiyesine borç kaydedilir; sonraki rezervasyon mutabakatından mahsup edilmek üzere `Settlement Recovery Offset` kaydı açılır.

---

## 7. Çift Taraflı Defter Kayıt Şablonu (Double-Entry Ingestion Template)

Reconciliation tamamlandığında **tarihsel rezervasyon satırları değiştirilmez (Append-Only)**, yeni bir settlement işlem grubu açılır:

```
Banka Transferi Netleştiğinde (BT_NET - 84,500 TRY Banka Girişi):
  DR: Banka Hesabı (TRY) (#102)                    84,500 TRY  (Nakit Girişi)
  DR: Kanal Komisyonu Yükümlülükleri (#329)        15,500 TRY  (Kanal Komisyonu Mahsubu)
      CR: Misafir / Kanal Alacakları Hesabı (#120)              100,000 TRY (Alacak Kapanışı)
```

---

## 8. Değişiklik Yapılacak Bileşenler (Affected Components)

1. **[`App\Models\Finance\ProviderSettlement`](file:///Users/macbookpro/repos/yalihan-os/app/Models/Finance/ProviderSettlement.php) [NEW]**
2. **[`App\Models\Finance\SettlementAllocation`](file:///Users/macbookpro/repos/yalihan-os/app/Models/Finance/SettlementAllocation.php) [NEW]**
3. **[`App\Models\Finance\BankTransaction`](file:///Users/macbookpro/repos/yalihan-os/app/Models/Finance/BankTransaction.php) [NEW]**
4. **[`App\Models\Finance\ReconciliationExecution`](file:///Users/macbookpro/repos/yalihan-os/app/Models/Finance/ReconciliationExecution.php) [NEW]**
5. **[`App\Services\Finance\SettlementReconciliationService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/Finance/SettlementReconciliationService.php) [NEW]**
6. **[`App\Services\FinancialLedgerService`](file:///Users/macbookpro/repos/yalihan-os/app/Services/FinancialLedgerService.php):** `recordSettlementReconciliation()` ve `recordOwnerDisbursement()` metodları.

---

## 9. Kabul Kriterleri ve Sertifikasyon Planı

- [ ] **Modalite Testi:** `BT_NET`, `BT_GROSS`, `VCC` akışlarının doğru hesaplanması.
- [ ] **Double-Deduction Guard Testi:** C4 kanal ücretinin C5'te mükerrer düşülmediğinin kanıtı.
- [ ] **Batch Match Testi:** $1$ Banka transferi $\leftrightarrow N$ Rezervasyon dağılım doğrulaması ($\Sigma \text{Net} = \text{Banka Tutar}$).
- [ ] **Discrepancy & Zero-Tolerance Testi:** $\Delta \ne 0$ durumunda otomatik posting yapılmaması, payout'un bloke kalması.
- [ ] **Immutable Execution Testi:** Replay durumunda mükerrer kayıt üretilmemesi; tarihsel rezervasyon kayıtlarının asla update/delete edilmemesi.
- [ ] **Tenant Isolation Testi:** Farklı kiracıların banka ve mutabakat hareketlerinin %100 izole olması.

---

## 10. Nihai Karar

$$\mathbf{Status: \quad C5\_SETTLEMENT\_RECONCILIATION\_CHARTER\_READY}$$
$$\mathbf{Implementation: \quad \text{STRICT HOLD (Awaiting SAAB Implementation GO)}}$$

---
*SAAB decides. Engineering implements. Antigravity verifies.*
