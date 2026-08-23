# C5 — Settlement & Reconciliation Discovery Report

**Authority:** SAAB / Strategic AI Architecture Board  
**Status:** 🔍 PROPOSED DISCOVERY (Read-Only — No Implementation)  
**Parent Capabilities:** C1 (Completion), C2 (Queue Safety), C3 (Owner Payable), C3.4 (Schema Convergence), C4.1 (Fee Policy), C4.2 (Channel Fee Ledger)  
**Date:** 2026-08-23  

---

## 1. Executive Summary & The Real Property Problem

### The Core Business Question
> *"OTA'nın 'ödedim / kestim' dediği para ile Yalıhan'ın banka hesabına gerçekten giren para aynı mı ve sistem bunu otomatik olarak mutabık hale getirebilir mi?"*

### Real Estate Operational Friction
Mevcut durumda gayrimenkul ve kısa dönem villa kiralama yönetiminde muhasebe ekipleri şu manuel süreçlere hapsolmaktadır:
1. **Çoklu Kaynak Uyuşmazlığı:** Airbnb, Booking.com, VRBO ve doğrudan banka havaleleri farklı tarihlerde, farklı para birimlerinde ve farklı kesinti modelleriyle banka hesabına girer.
2. **Toplu Ödeme (Batch Payout) Karmaşası:** Airbnb/Booking, tek bir rezervasyon için ayrı havale göndermek yerine, aynı gün gerçekleşen 7 farklı villa konaklamasının net tutarını tek bir toplu EFT/SWIFT transferi olarak bankaya yatırır.
3. **Gizli Kesintiler ve Chargeback Riski:** Kanalın tahakkuk aşamasında bildirdiği komisyon ile gerçek payout anında uyguladığı döviz kuru marjı, kart işlem komisyonu, tazminat veya iade kesintisi fark gösterebilir.
4. **Hatalı Ev Sahibi Ödemesi (Over-Payout Danger):** Bankaya eksik para girdiğinde veya iptal/iade kesintisi oluştuğunda, sistem ev sahibine tahakkuk eden brüt tutar üzerinden tam ödeme yaparsa Yalıhan doğrudan finansal zarara uğrar.

**C5'in Amacı:** Kanal rezervasyon tahakkuku (`Accrual`) ile gerçek banka nakit akışı (`Settlement`) arasındaki boşluğu kapatmak; uyuşmazlıkları tolerans limitleri dahilinde otomatik çözmek veya insan denetimine (reconciliation queue) yönlendirmektir.

---

## 2. Inbound Payout & Settlement Data Topologies

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                            INBOUND DATA STREAMS                                  │
├────────────────────────────────────────┬─────────────────────────────────────────┤
│        STREAM A: OTA / Channel Payout  │      STREAM B: Bank Statements          │
│   (Airbnb / Booking / Channex / VCC)   │   (Garanti / İş / Akbank MT940/API)     │
├────────────────────────────────────────┼─────────────────────────────────────────┤
│ • Payout Batch ID                      │ • Bank Transaction ID / Dekont No       │
│ • External Reservation IDs             │ • Settlement Value Date (Valör)         │
│ • Channel Fee Withheld                 │ • Bank Account IBAN / Currency          │
│ • Net Payout Amount                    │ • Actual Net Received Amount            │
│ • Payout Execution Date                │ • Sender Name & Wire Description        │
└────────────────────────────────────────┴─────────────────────────────────────────┘
                                     │
                                     ▼
                   ┌───────────────────────────────────┐
                   │    C5 RECONCILIATION ENGINE       │
                   │  (Matching & Discrepancy Rules)   │
                   └───────────────────────────────────┘
                                     │
                 ┌───────────────────┴───────────────────┐
                 ▼                                       ▼
       [ EXACT / WITHIN TOLERANCE ]             [ DISCREPANCY / SHORT-PAYOUT ]
                 │                                       │
                 ▼                                       ▼
       • Settle Ledger Accounts                 • Block Owner Payout Release
       • Mark RECONCILED                        • Route to Exception Console
       • Release Owner Payout                   • Trigger Operator Adjustment
```

### Stream A: Kanal Payout Veri Modelleri
1. **Channex / OTA Push/Feed:**
   - Rezervasyon anında gelen veri tahmini veya bildirilen kanonik veridir (`PROVIDER_REPORTED`).
   - Gerçek ödeme anında OTA finansal raporları (Financial Reporting API / Webhook) `payout_reference`, `paid_amount`, `withheld_tax`, `withheld_commission` detaylarını üretir.
2. **Booking.com Virtual Credit Card (VCC):**
   - Check-in tarihinde aktifleşen sanal kart.
   - Yalıhan POS/sanal POS entegrasyonu üzerinden çekim yapılır $\rightarrow$ 1-to-1 doğrudan banka settlement'ı oluşur.
3. **Airbnb Direct Bank Payout:**
   - Check-in'den 24 saat sonra banka transferi tetiklenir.
   - Payout Reference (ör: `HM12345678`) banka dekont açıklamasında yer alır.

### Stream B: Banka / Sanal POS Hesap Ekstresi
- **Kaynaklar:** Open Banking API entegrasyonu, otomatik MT940 parser veya banka CSV içe aktarma motoru.
- **Kritik Alanlar:** `iban`, `islem_tarihi`, `tutar`, `para_birimi`, `dekont_no`, `aciklama`.

---

## 3. Matching & Discrepancy Topology

### Eşleştirme Matrisi (Matching Engine)
| Model | Senaryo | Otomasyon Mekanizması |
| :--- | :--- | :--- |
| **1-to-1 Exact** | Tek rezervasyon $\leftrightarrow$ Tek banka hareketi | Referans kodu veya tutar + tarih eşleşmesi ile tam otomatik mutabakat. |
| **Many-to-1 (Batch)** | 1 Banka Transferi $\leftrightarrow$ $N$ Rezervasyon | OTA Payout ID ile gruptaki rezervasyonların toplamının banka transfer tutarına eşitlenmesi ($\sum \text{Net} = \text{Banka Tutarı}$). |
| **Split / Milestone** | 1 Rezervasyon $\leftrightarrow$ 2 Banka Hareketi | Peşinat + Kalan bakiye eşleştirmesi. İki parça tamamlanmadan tam mutabakat verilmez. |

### Discrepancy & Tolerance Engine
Tahakkuk eden beklenen net tutar ($N_{\text{exp}}$) ile bankaya yatan tutar ($N_{\text{act}}$) arasındaki fark ($\Delta = |N_{\text{exp}} - N_{\text{act}}|$):

1. **Exact Match ($\Delta = 0$):**
   - Otomatik `RECONCILED` statüsü.
   - Ev sahibi hakedişi onaylanır (`PAYOUT_RELEASE_APPROVED`).
2. **Tolerans İçi Fark ($\Delta \le \text{Threshold}$, örn. $\le 25\text{ TRY}$ veya $\le 0.2\%$):**
   - Küçük döviz kuru çevrim farkları veya banka transfer masrafları.
   - Sistem farkı otomatik olarak `Kambiyo / Yuvarlama Gideri` hesabına atar ve işlemi `RECONCILED_WITH_ADJUSTMENT` olarak kapatır.
3. **Tolerans Dışı Eksik Ödeme ($\Delta > \text{Threshold}$, Short-Payment):**
   - Kanal beklenenden daha az ödeme göndermiştir (ör. ek servis kesintisi veya ceza).
   - Rezervasyon `DISCREPANCY_SHORT_PAYMENT` statüsüne alınır.
   - Ev sahibi ödemesi **bloke kalmaya devam eder**.
4. **İptal & Chargeback (Negative Settlement):**
   - Misafir kalış sonrası itiraz etmiş veya kanal ceza kesmiştir.
   - Ev sahibine para henüz ödenmediyse sahip tahakkuku revize edilir; ödendiyse sonraki rezervasyon hakedişinden mahsup (`Settlement Recovery Offset`) kaydedilir.

---

## 4. Double-Entry Ledger Settlement Architecture

C4.2 ile kurulan tahakkuk yapısının C5 ile gerçek nakit hareketiyle kapanış modeli:

```
────────────────────────────────────────────────────────────────────────────────
FAZ 1: REZERVASYON TAHAKKUKU (C4.2 - Tamamlanan Durum)
────────────────────────────────────────────────────────────────────────────────
1. İlk Rezervasyon:
   DR: Misafir / Kanal Alacakları Hesabı (#120)    100,000 TRY
       CR: Konaklama / Kira Gelirleri (#600)                     100,000 TRY

2. Finansal Tamamlama (C4.2 Triple Split):
   DR: Konaklama / Kira Gelirleri (#600)            15,500 TRY
       CR: Kanal Komisyonu Yükümlülükleri (#329)                  15,500 TRY
   DR: Konaklama / Kira Gelirleri (#600)            15,000 TRY
       CR: Komisyon Gelirleri (#602)                              15,000 TRY
   DR: Konaklama / Kira Gelirleri (#600)            69,500 TRY
       CR: Sahip Yükümlülükleri (#320/336)                        69,500 TRY

────────────────────────────────────────────────────────────────────────────────
FAZ 2: C5 MUTABAKAT & BANKA GİRİŞİ (Settlement & Reconciliation)
────────────────────────────────────────────────────────────────────────────────
Banka Hesabına Net 84,500 TRY Yattığında (100,000 Gross - 15,500 Kanal Kesintisi):
   DR: Banka Hesabı (TRY) (#102)                    84,500 TRY  (Gerçek Nakit Girişi)
   DR: Kanal Komisyonu Yükümlülükleri (#329)        15,500 TRY  (Kanal Kesintisi Mahsubu)
       CR: Misafir / Kanal Alacakları Hesabı (#120)              100,000 TRY (Alacak Kapanışı)

Sonuç:
• Alacak hesabı (#120) 0'a iner (Tam kapandı).
• Kanal yükümlülüğü (#329) 0'a iner (Kanal komisyonu mahsup edildi).
• Banka hesabına (#102) net 84,500 TRY girdi.
• Şirket kasasında 15,000 TRY komisyon geliri, ev sahibine ödenecek 69,500 TRY yükümlülük kaldı.

────────────────────────────────────────────────────────────────────────────────
FAZ 3: EV SAHİBİNE ÖDEME ÇIKIŞI (Owner Payout Execution)
────────────────────────────────────────────────────────────────────────────────
Ev Sahibine Bankadan 69,500 TRY Gönderildiğinde:
   DR: Sahip Yükümlülükleri (#320/336)              69,500 TRY  (Borç Kapanışı)
       CR: Banka Hesabı (TRY) (#102)                              69,500 TRY  (Nakit Çıkışı)

Nihai Bilanço Dengesi:
• Bankada Kalan Net Nakit: 15,000 TRY (Yalıhan Net Komisyonu)
• Komisyon Gelirleri: 15,000 TRY
• Tüm ara alacak/borç hesapları bakiye = 0.
```

---

## 5. Proposed Domain Boundaries & Entities (C5 Blueprint)

### Proposed Entities
1. `ChannelPayoutBatch`
   - `id`, `tenant_id`, `external_channel`, `batch_reference`, `total_gross`, `total_channel_fee`, `total_net`, `currency`, `payout_date`, `status` (`pending`, `reconciled`, `discrepant`)
2. `ChannelPayoutItem`
   - `id`, `payout_batch_id`, `reservation_id`, `external_reservation_id`, `gross_amount`, `fee_amount`, `net_amount`, `status`
3. `BankTransaction`
   - `id`, `tenant_id`, `bank_account_id`, `transaction_date`, `valör_date`, `amount`, `currency`, `reference_text`, `raw_payload`, `match_status` (`unmatched`, `matched`, `ignored`)
4. `ReconciliationRecord`
   - `id`, `tenant_id`, `reservation_id`, `payout_item_id`, `bank_transaction_id`, `discrepancy_amount`, `status` (`reconciled_exact`, `reconciled_with_tolerance`, `discrepancy_held`), `reconciled_by` (system/user_id), `reconciled_at`

### Service & Workflow Topology
- `BankStatementIngestService`: MT940 / CSV / API banka hareketlerini normalize eder.
- `ChannelSettlementIngestService`: OTA payout raporlarını ve webhooklarını ingest eder.
- `ReconciliationMatcherService`: Banka hareketleri ile OTA payout item'larını kurallara göre eşleştirir.
- `DiscrepancyResolutionService`: Tolerans kontrolü, kambiyo farkı muhasebeleştirmesi veya bloke koyma kararlarını yönetir.

---

## 6. Risk Analysis & SAAB Governance Gates

| Risk | Etki | Önleme & Tasarım Kuralı |
| :--- | :--- | :--- |
| **Erken Ev Sahibi Ödemesi** | Yüksek Finansal Kayıp | C5 mutabakatı tamamlanmadan hiçbir rezervasyon için otomatik ödeme emri tetiklenemez (`Strict Payout Gate`). |
| **Döviz / Kur Dalgalanması** | Muhasebe Dengesizliği | Sabitlenen `booking_fx_rate` ile gerçek banka valör kuru arasındaki fark `Kambiyo Karı / Zararı` hesabına şeffaf double-entry olarak yansıtılır. |
| **Toplu Ödemede Kısmi Uyuşmazlık** | Operasyonel Kilitlenme | 10 rezervasyonluk bir batch'te 9'u tam uyumlu, 1'i uyuşmazlıklı ise, uyumlu 9 rezervasyon mutabık kılınır; sadece 1 rezervasyon incelemeye ayrılır. |

---

## 7. Next Steps & Charter Roadmap

1. **SAAB Review:** İşbu Discovery dokümanının iş mantığı ve muhasebe akışının incelenmesi.
2. **C5 Implementation Charter:** Veritabanı şemaları, servis kontratları ve state machine kurallarını içeren resmi charter'ın hazırlanması.
3. **Geliştirme & Test Aşaması:** Mock banka ekstreleri ve çoklu kanal batch testleri ile TDD odaklı geliştirme.

---
*SAAB decides. Engineering implements. Antigravity verifies.*
