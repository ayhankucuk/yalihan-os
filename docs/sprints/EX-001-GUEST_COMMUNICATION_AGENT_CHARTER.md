# SAAB Execution Charter v1.0

> **Mission ID:** EX-001
> **Capability:** Guest Communication Agent
> **Status:** 🟢 AUTHORIZED
> **Date:** 2026-08-06

---

## Başarı Sorusu (Success Question)

> YALIHAN, misafir iletişiminin tekrar eden operasyonlarını güvenli, tenant-safe ve ölçülebilir şekilde otomatikleştirerek Business Automation Index'i (BAI) artırabiliyor mu?

---

## Business Hypothesis

> "We believe Guest Communication Agent will increase BAI by approximately 8% by automatically handling repetitive guest communication while preserving customer satisfaction and reducing manual response time."

---

## Capability Boundary

### ✅ Yapar

* Karşılama mesajı
* Check-in bilgisi
* Yol tarifi
* Wi-Fi bilgileri
* Havuz bakım bilgilendirmesi
* Sık sorulan sorular (FAQ)
* Plaj / restoran önerileri
* Check-out hatırlatması
* Değerlendirme (review) isteme mesajı
* Çok dilli mesaj oluşturma

### ❌ Yapmaz

* Fiyat pazarlığı
* Rezervasyon onayı
* Para iadesi kararı
* İptal kararı
* Ev sahibi adına hukuki taahhüt
* Manuel override gerektiren durumlar

---

## Execution KPI'ları

| KPI | Başlangıç | Hedef |
|-----|-----------|-------|
| Ortalama ilk yanıt süresi | Mevcut ölçüm | %80 iyileşme |
| Otomatik cevap oranı | Mevcut ölçüm | ≥ %80 |
| İnsan müdahalesi | Mevcut ölçüm | ≤ %20 |
| Misafir memnuniyeti | Mevcut puan | Koru veya artır |
| Manuel zaman tasarrufu | 0 | +5-10 saat/hafta |
| BAI katkısı | 0 | +8% hedef |

---

## Executive Exit Criteria

Guest Communication Agent ancak aşağıdaki koşullar birlikte sağlanırsa READY kabul edilir:

* ✅ Engineering Gate PASS
* ✅ Architecture Gate PASS
* ✅ Business Gate: BAI etkisi ölçüldü
* ✅ AI Gate: Otonomi hedefi karşılandı
* ✅ Customer Gate: Memnuniyet korunuyor veya artıyor
* ✅ Learning tamamlandı
* ✅ Executive Report üretildi
* ✅ CRS ≥ 90

---

## Expected Sprint Output

| Alan | Değer |
|------|-------|
| Capability | Guest Communication Agent |
| Status | READY |
| Expected BAI | +8% |
| Actual BAI | ? |
| Automation Rate | ? |
| Manual Hours Saved | ? |
| Customer Satisfaction | ? |
| CRS | ? |
| Decision | READY / CONDITIONAL / NOT READY |

---

## Mimari Yaklaşım — Event Tabanlı

Guest Communication Agent olay (event) tabanlı capability olarak tasarlanır:

```
ReservationConfirmed
        │
        ▼
GuestCommunicationAgent
        ├── Welcome Message
        ├── Check-in Instructions
        ├── Local Guide Suggestions
        ├── Mid-stay Follow-up
        ├── Check-out Reminder
        └── Review Request
```

Bu yaklaşım, gelecekte WhatsApp, Airbnb, Booking.com, Telegram veya e-posta kanallarını aynı iş akışına bağlamayı kolaylaştırır.

---

## Teslim Kriterleri

### 1. Engineering
* Testler PASS
* Regresyon PASS
* Tenant isolation doğrulandı
* Replay-safe davranış doğrulandı

### 2. Business
* Beklenen BAI: +8%
* Gerçekleşen BAI ölçüldü
* Manuel saat kazanımı hesaplandı

### 3. AI
* Otomatik cevap oranı
* İnsan eskalasyon oranı
* Güvenli otonomi seviyesi

### 4. Customer
* Ortalama ilk yanıt süresi
* Misafir memnuniyeti
* Çok dilli iletişim başarısı

### 5. Executive
* CRS hesaplandı
* Executive Report üretildi
* READY / CONDITIONAL / NOT READY kararı

---

## SAAB Authorization

**Status: 🟢 IMPLEMENTATION AUTHORIZED**

Mission EX-001, yalnızca bir agent geliştirme çalışması değil; Execution Era'nın ilk saha doğrulaması olacak.

---

## SAAB Execution Question

> "Guest Communication Agent, misafir iletişim operasyonlarını güvenli, tenant-safe ve ölçülebilir şekilde otomatikleştirerek hedeflenen BAI artışına ulaşıyor mu?"

---

## Uygulama Öncelikleri

### 1. Domain Events
* `ReservationConfirmed`
* `CheckInApproaching`
* `MidStayReached`
* `CheckOutApproaching`
* `ReservationCompleted`

### 2. Message Policy
* Şablon seçimi
* Dil seçimi
* Kanal seçimi (Airbnb, WhatsApp, e-posta vb.)
* Gönderim kuralları

### 3. Delivery Pipeline
* Kuyruk (Queue)
* Retry
* Idempotency
* Audit Log

### 4. Metrics Collection
* Yanıt süresi
* Otomasyon oranı
* İnsan müdahalesi
* BAI katkısı

---

## Sprint Exit Evidence

* ✅ Business Hypothesis doğrulaması
* ✅ Expected vs Actual BAI
* ✅ Engineering Evidence (test + regresyon)
* ✅ AI Evidence (otonomi oranı)
* ✅ Customer Evidence (yanıt süresi, memnuniyet)
* ✅ Executive Report
* ✅ CRS hesaplaması
* ✅ Learning & Variance analizi

---

## Operasyonel Kanıt Toplama

| KPI | Ölçüm Kaynağı |
|-----|----------------|
| Ortalama ilk yanıt süresi | Mesaj zaman damgaları |
| Otomatik cevap oranı | Agent logları |
| İnsan eskalasyon oranı | Manuel müdahale kayıtları |
| Mesaj teslim başarısı | Delivery logları |
| Misafir memnuniyeti | Değerlendirmeler / geri bildirimler |
| Manuel saat tasarrufu | Haftalık operasyon karşılaştırması |
| BAI katkısı | Executive Report |

---

## Production Certified Eşiği

EX-001 Production Certified için şu koşullar birlikte sağlanmalı:

* Engineering kanıtı tamam
* Business kanıtı tamam
* AI kanıtı tamam
* Customer kanıtı tamam
* Executive Report tamam
* CRS eşiği karşılandı
* Gerçek operasyon verisiyle BAI etkisi doğrulandı

---

## Referans

* `docs/EXECUTION_ERA_STANDARD.md` — Çalışma standardı
* `docs/templates/SPRINT_CLOSURE_TEMPLATE.md` — Kapanış şablonu
* `memory/PRODUCT_METRICS.md` — BAI ve CRS hesaplama
