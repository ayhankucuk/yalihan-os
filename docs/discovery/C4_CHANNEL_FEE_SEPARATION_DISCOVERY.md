# C4 — Channel Fee Separation Discovery & Architecture Charter

**Baseline:** `bc5c037` (Report: `6b12871`)  
**Tarih:** 2026-08-23  
**Rol:** SAAB Strategic Research / Antigravity  
**Mod:** READ-ONLY Discovery / Charter Proposal

---

## 1. Yönetici Özeti & Problem Tanımı

C1–C3 aşamaları ile rezervasyon tamamlandığında brüt tutardan Yalıhan yönetim komisyonunun hesaplanması, ev sahibi cari tahakkukunun yapılması ve `PayoutReadinessService` ile operatör onayına sunulması production seviyesinde kanıtlanmıştır.

Ancak günümüz kiralama operasyonlarında Airbnb, Booking.com ve VRBO gibi harici satış kanalları (OTA) brüt rezervasyon tutarı üzerinden **%3 ile %18 arasında kanal komisyonu (Channel / Host Fee)** kesmektedir.

Bugün Yalıhan OS'ta:
$$\text{Brüt Tutar (100.000 TL)} - \text{Yalıhan Komisyonu (15.000 TL)} = \text{Ev Sahibi Hakedişi (85.000 TL)}$$

Eğer OTA kanalı 15.000 TL kanal komisyonu kesmişse ve bu tutar muhasebeleştirilmemişse, Yalıhan ev sahibine 85.000 TL ödediğinde bankaya giren net para $100.000 - 15.000 = 85.000$ TL olacağından **Yalıhan'ın 15.000 TL komisyon geliri sıfırlanır / zarar oluşur**.

Bu nedenle **C4 — Channel Fee Separation**, gerçek para dağıtımı öncesinde mutlak bir finansal zorunluluktur.

---

## 2. Mevcut Repository Durumu & Bulgular

1. **`property_reservations` Tablosu:**
   - `external_channel` ('airbnb', 'booking_com', 'direct', 'channex') ve `external_reservation_id` alanları mevcuttur.
   - **Eksik:** `channel_fee_amount`, `channel_fee_rate`, `net_channel_payout` alanları veritabanında henüz tanımlı DEĞİLDİR.

2. **Channex Webhook & DTO (`ChannexReservationPayload`):**
   - Channex'ten gelen `total_price`, `currency`, `channel_name` DTO tarafından okunup `property_reservations.total_amount` alanına brüt olarak yazılmaktadır.
   - Kanal komisyonu ham payload'dan ayrıştırılmamakta veya kural tabanlı türetilmemektedir.

3. **Çift Taraflı Muhasebe (`FinancialLedgerService`):**
   - `Misafir Alacakları Hesabı` (120), `Konaklama Gelirleri` (600), `Komisyon Gelirleri` (602), `Sahip Yükümlülükleri` (320) hesapları mevcuttur.
   - **Eksik:** `Kanal Komisyon Giderleri / OTA Yükümlülükleri Hesabı` (760 / 329) henüz hesap planına eklenmemiştir.

---

## 3. Kanal Bazlı Komisyon Modelleri (Business Rules)

| Kanal | Standart Komisyon Oranı | Tahsilat Şekli | Yalıhan OS Kuralı |
|---|---|---|---|
| **Airbnb (Host-Only Fee)** | **%14.00 – %16.00** | Airbnb net ödeme yapar (Brüt - %15) | Brüt tutardan kanal payı düşülür |
| **Airbnb (Split Fee)** | **%3.00** (Host Fee) | Misafirden %14, ev sahibinden %3 | Brüt tutardan %3 kanal payı düşülür |
| **Booking.com** | **%15.00 – %18.00** | Acente brüt tahsil eder, Booking fatura keser | Komisyon tahakkuku borç kaydedilir |
| **Direct / Yalıhan Web** | **%0.00** | Kanal komisyonu yoktur | 0 kesinti |

---

## 4. Matematiksel & Muhasebesel Akış Tasarımı

### Formül (Kanonik Net Hakediş):
$$\text{Gross Amount} = \text{Rezervasyon Brüt Tutarı (Misafirin Ödediği)}$$
$$\text{Channel Fee} = \text{Gross Amount} \times \text{Channel Fee Rate}$$
$$\text{Yalıhan Commission} = \text{Gross Amount} \times \text{Yalıhan Commission Rate}$$
$$\text{Net Owner Entitlement} = \text{Gross Amount} - \text{Channel Fee} - \text{Yalıhan Commission}$$

*Örnek Hesaplama (100.000 TL Rezervasyon, Booking.com %15, Yalıhan Tam Yönetim %15):*
- **Brüt Rezervasyon:** 100.000 TL
- **OTA Kanal Komisyonu (%15):** 15.000 TL
- **Yalıhan Hizmet Komisyonu (%15):** 15.000 TL
- **Ev Sahibine Net Hakediş:** **70.000 TL**

---

### Çift Taraflı Defter-i Kebir Kayıt Zinciri (Double-Entry Ledger):

1. **Rezervasyon Oluşumu (Initial Booking):**
   - **Borç (Debit):** `Misafir / Kanal Alacakları Hesabı` (100.000 TL)
   - **Alacak (Credit):** `Konaklama / Kira Gelirleri Hesabı` (100.000 TL)

2. **Finansal Tamamlama & Tahakkuk (Financial Completion & Accrual):**
   - **TX1 (Kanal Komisyon Ayrıştırması):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` (15.000 TL)
     - **Alacak (Credit):** `Kanal Komisyon Yükümlülükleri / Gideri` (15.000 TL)
   - **TX2 (Yalıhan Komisyonu):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` (15.000 TL)
     - **Alacak (Credit):** `Komisyon Gelirleri Hesabı` (15.000 TL)
   - **TX3 (Ev Sahibi Hakedişi):**
     - **Borç (Debit):** `Konaklama / Kira Gelirleri` (70.000 TL)
     - **Alacak (Credit):** `Sahip Yükümlülükleri Hesabı` (70.000 TL)

*Denge Kontrolü:* Konaklama Gelirleri $100.000 \text{ (CR)} - (15.000 + 15.000 + 70.000) \text{ (DB)} = 0$ TL (Tam Denge ✅).

---

## 5. C4 Implementasyon Alt Dalgaları (Proposed Waves)

1. **C4.1 — Channel Fee Snapshot & Rules Engine:**
   - `channel_financial_rules` tablosu / config (Airbnb %15 / %3, Booking %15, Direct %0).
   - Rezervasyon oluşumunda `channel_fee_rate_snapshot` dondurulması.
2. **C4.2 — Double-Entry Channel Fee Ledger Accrual:**
   - `FinancialLedgerService` içine `recordChannelFeeAccrual` eklenmesi.
   - `Kanal Komisyon Yükümlülükleri Hesabı` tescili.
3. **C4.3 — Payout Readiness UI & API Net Entitlement Güncellemesi:**
   - `PayoutReadinessService` ve `payout-ready.blade.php` arayüzüne `Kanal Komisyonu`, `Yalıhan Komisyonu` ve `Net Ev Sahibi Hakedişi` sütunlarının eklenmesi.
4. **C4.4 — OTA Payout Reversal & Idempotency Tests:**
   - İptal durumunda kanal komisyonu ters kaydı (reversal).

---

## 6. SAAB Karar Kapısı

Bu charter onaylandığında, C4.1 aşaması ile kanal komisyon kurallarının tescili ve veritabanı şema genişletilmesine başlanabilir.
