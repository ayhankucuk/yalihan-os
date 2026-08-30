# TC-GT-09 — CRM Eşleşmesi Gerçek Sonuçları + Danışman Görev Otomasyonu

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-27T00:00:00Z (UTC) [TR: 2026-08-27 03:00:00 +03:00]
- **Evidence Level:** `PRODUCTION_VERIFIED`
- **Production Authorization:** `AUTHORIZED (Production DB port 8002)`
<!-- ───────────────────────────────────────────────────────────── -->

**Veritabanı:** `yalihanai_v2_production`

---

## 1. Amaç

Golden Thread'in son iki adımını doğrulamak:

1. **CRM eşleşmesinin gerçek sonuçları** — eşleşen talep varken `reverseMatch()` gerçek bir eşleşme üretiyor mu? Danışmana bildirim gidiyor mu?
2. **Danışman görevinin otomatik üretilmesi** — CRM eşleşme akışı bir danışman görevi (Gorev) otomatik oluşturuyor mu? *(Bu adımın mevcut tasarımda bulunmadığı doğrulanmıştır — bkz. §4.)*

---

## 2. Ön Bulgular (Kod İncelemesi)

### 2.1 CRM Eşleşme Akışı — `FindMatchingDemands` Listener

`IlanCrudService::store()` → `IlanCreated` event → `FindMatchingDemands` listener (queue):

1. `reverse_matching_started` loglar
2. `SmartPropertyMatcherAI::reverseMatch($ilan)` çağırır
3. Her eşleşme için (score >= 80):
   - Score > 90 ise `HandleUrgentMatch` job'ı dispatch eder (Telegram alert)
   - `NewMatchingListingFound` bildirimini danışmana gönderir
4. `reverse_matching_completed` loglar

**Kritik bulgu:** Listener **Gorev (görev) oluşturmaz.** Yalnızca bildirim + (score>90 ise) Telegram alert üretir.

### 2.2 Danışman Görev (Gorev) Oluşturma Noktaları

`Gorev::create()` çağrıları şu akışlarda bulunur:
- `ProjeService` (proje görevleri)
- `CreateOperationalTasksJob` / `ProcessReservationCompletedJob` (rezervasyon operasyonel görevleri)
- `GorevApiController` / `GorevController` (manuel oluşturma)
- `FollowUpAutomationService` (CRM lead takip görevleri — **Lead** bazlı, ilan-talep eşleşmesi değil)
- `VoiceCommandProcessor` (sesli komut)
- `OperationalGorevService` (rezervasyon temizlik/teslim)
- `TelegramAIBotService` (telegram bot)

**Hiçbiri CRM eşleşme akışı tarafından tetiklenmez.**

---

## 3. Doğrulama — Production DB Üzerinde

### 3.1 Test Verisi

Eşleşen bir Talep + İlan oluşturuldu:

| Varlık | Alan | Değer |
|--------|------|-------|
| Talep | id | 1 |
| Talep | talep_durumu | `yayinda` (AKTIF) |
| Talep | alt_kategori_id | 8 (Villa) |
| Talep | ilce_id | 1 (Bodrum) |
| Talep | min_fiyat / max_fiyat | 9.000.000 / 11.000.000 |
| Talep | danisman_id | 4 (Atılay) |
| İlan | id | 18 |
| İlan | alt_kategori_id | 8 (Villa) |
| İlan | ilce_id | 1 (Bodrum) |
| İlan | fiyat | 10.000.000 (bütçe ortası) |

### 3.2 `reverseMatch()` Sonucu

```
match_sayisi: 1
score: 85  (eşik: 80 → GEÇTİ)
breakdown: { location: 40, price: 30, features: 15 }
```

- **Konum (40/40):** Aynı ilçe (Bodrum) → tam puan
- **Fiyat (30/30):** İlan fiyatı bütçe ortasında → tam puan
- **Özellik (15/30):** Aranan özellik yok → orta puan

### 3.3 `NewMatchingListingFound` Bildirimi

```
notification_id: a29a68e9-4420-40d8-98fe-c8122bd1261a
type: App\Notifications\NewMatchingListingFound
notifiable_id: 4 (danışman Atılay)
data:
  tip: matching_listing_found
  title: "Yeni Eşleşme Bulundu"
  message: "Müşteriniz Atılay Danışman için yeni bir eşleşme bulundu: Test-09: Bodrum Merkez Villa (Uyum: %85)"
  score: 85
```

### 3.4 AI Log Kanıtı (`storage/logs/ai-2026-08-27.log`)

```
reverse_matching_started (SmartPropertyMatcherAI) ilan_id=18
reverse_matching_scored_results ilan_id=18 filtered_count=1 all_scores=[85.0] scores_above_80=1
reverse_matching_completed (SmartPropertyMatcherAI) ilan_id=18 results_count=1
reverse_matching_started (FindMatchingDemands) ilan_id=18
reverse_match_notification_sent ilan_id=18 talep_id=1 score=85.0 urgency_level=NORMAL danisman_id=4
reverse_matching_completed (FindMatchingDemands) ilan_id=18 matched_count=1 notification_count=1
```

### 3.5 Danışman Görev (Gorev) Kontrolü

```
gorev_sayisi: 0
gorev_otomatik_olusturuldu_mu: false
```

CRM eşleşme akışı **danışman görevi oluşturmadı.**

---

## 4. Sonuç

| Adım | Sonuç |
|------|-------|
| **CRM eşleşmesi gerçek sonuçları** | ✅ `VERIFIED` — eşleşen talep varken `reverseMatch()` gerçek eşleşme üretti (score=85), `NewMatchingListingFound` bildirimi danışmana gönderildi, `reverse_match_notification_sent` loglandı |
| **Danışman görev otomasyonu** | ⚠️ `CONFIRMED_NOT_AUTO_GENERATED` — CRM eşleşme akışı danışman görevi (Gorev) oluşturmuyor; yalnızca bildirim + (score>90 ise) Telegram alert üretiyor |

### 4.1 Kesin Durum İfadesi

> Golden Thread'in ilan oluşturma, onay, yayınlama ve CRM eşleşmesi/bildirim hattı production'da doğrulandı. CRM eşleşmesinden otomatik danışman görevi üretimi mevcut tasarımda bulunmuyor; bu ayrı bir ürün geliştirme kararıdır.

### 4.2 Mimari Not

CRM eşleşme akışı (`FindMatchingDemands`) bir **danışman görevi (Gorev) otomatik üretmez.** Bu bir bug değil, mevcut tasarım davranışıdır: eşleşme, danışmana **bildirim** olarak iletilir ve danışmanın aksiyon alması beklenir. Danışman görevi üretimi ayrı bir mekanizmadır (`FollowUpAutomationService` — Lead tabanlı, `OperationalGorevService` — rezervasyon tabanlı).

---

## 5. Ayrı Ürün Geliştirme Maddesi — Otomatik Danışman Görevi Üretimi

> ⚠️ **Kapsam dışı:** Bu madde Golden Thread sertifikasyonuna **DAHİL DEĞİLDİR.** Ayrı bir ürün geliştirme maddesi olarak ele alınır. Mevcut davranış (bildirim tabanlı) korunur; görev üretimi, aşağıdaki kararlar netleştirilmeden geliştirilmemelidir.

Otomatik görev üretimi **yeni özellik** olarak planlanmalıdır. Aşağıdaki kapsam, görev tipi, öncelik, son tarih, sorumlu danışman ve duplicate önleme kuralı belirlenmeden kodlanmamalıdır.

### 5.1 Önerilen Akış

```
eşleşme → idempotent Gorev oluşturma → danışmana bildirim → audit kaydı → tekrar tetiklenmeye karşı koruma
```

### 5.2 Kodlanmadan Önce Belirlenmesi Gereken Kararlar

| Karar | Açıklama |
|-------|----------|
| **Kapsam** | Hangi eşleşme skoru eşiğinde görev üretilecek? (örn. score >= 80) |
| **Görev tipi** | `Gorev` tipi / kategorisi ne olacak? (örn. "CRM Eşleşme Takibi") |
| **Öncelik** | Görev önceliği nasıl belirlenecek? (score / urgency_level bazlı mı?) |
| **Son tarih** | Görevin due date'i nasıl hesaplanacak? (örn. eşleşme + N gün) |
| **Sorumlu danışman** | Görev, talep sahibi danışmana mı, ilan sahibine mi atanacak? |
| **Duplicate önleme** | Aynı ilan-talep çifti için tekrar görev üretilmesi nasıl engellenecek? (idempotency anahtarı) |

### 5.3 Uygulama Noktası

Bu özellik `FindMatchingDemands` listener'ına eklenmelidir. Mevcut davranış (bildirim) korunmalı; görev üretimi bildirime **ek** bir adım olarak eklenmelidir.

---

## 6. Temizlik

Test verileri production'dan temizlendi:
- İlan 18 → silindi
- Talep 1 → silindi
- Bildirim → silindi
- Kalan ilan: 0, kalan talep: 0