# CQRS Projection Tenant + Rebuild — Mimari Araştırma Raporu

> **Tarih:** 2026-09-06
> **Spec:** `docs/architecture/cqrs-projection-research-spec-2026-09-06.md`
> **Mod:** Architect (araştırma + tasarım, kod yazma YOK)
> **Durum:** Tamamlandı

---

## İçindekiler

1. [Yönetici Özeti](#1-yönetici-özeti)
2. [Mevcut Durum Analizi](#2-mevcut-durum-analizi)
3. [Projection Zinciri 1 — ListingSearchProjection](#3-projection-zinciri-1--listingsearchprojection)
4. [Projection Zinciri 2 — ListingVelocityProjection](#4-projection-zinciri-2--listingvelocityprojection)
5. [Projection Zinciri 3 — MarketTrendProjection](#5-projection-zinciri-3--markettrendprojection)
6. [Projection Zinciri 4 — BuyerInterestProjection](#6-projection-zinciri-4--buyerinterestprojection)
7. [Projection Zinciri 5 — TalepMatchProjection](#7-projection-zinciri-5--talepmatchprojection)
8. [Projection Zinciri 6 — BuyerIntentProjection](#8-projection-zinciri-6--buyerintentprojection)
9. [Tenant İzolasyon Stratejisi](#9-tenant-izolasyon-stratejisi)
10. [Writer Tasarımı](#10-writer-tasarımı)
11. [Rebuild Stratejisi](#11-rebuild-stratejisi)
12. [rand() Fallback Kaldırma Planı](#12-rand-fallback-kaldırma-planı)
13. [Idempotency Stratejisi](#13-idempotency-stratejisi)
14. [Risk ve Bağımlılık Analizi](#14-risk-ve-bağımlılık-analizi)
15. [Önerilen Uygulama Sırası (Phase'ler)](#15-önerilen-uygulama-sırası-phaseler)
16. [Kabul Kriterleri — 10 Sorunun Cevapları](#16-kabul-kriterleri--10-sorunun-cevapları)
17. [Ek: Reader Sözleşme Matrisi](#17-ek-reader-sözleşme-matrisi)

---

## 1. Yönetici Özeti

YALIHAN OS'deki 6 CQRS read-model projection tablosunun tamamı **boştur, tenant_id kolonu yoktur ve writer'ları ya yoktur ya da simulation/çağrılmıyor durumundadır.** Bu rapor, her projection için tam `kaynak veri → writer/event → projection schema → tenant scope → reader → beklenen sonuç` zincirini tanımlar ve 10 kabul kriterine net cevaplar sunar.

### Kritik Bulgular

| # | Bulgu | Etki |
|---|-------|------|
| 1 | 6/6 projection tablosu boş — AI servisleri `rand()` ile rastgele veri üretiyor | Production AI önerileri anlamsız |
| 2 | 6/6 projection'da `tenant_id` yok — `BelongsToTenant` trait kullanılmıyor | Çoklu tenant izolasyonu yok |
| 3 | `city`/`district` alanlarında tip uyumsuzluğu: 3 reader string (il adı) bekler, 1 reader int (il ID) bekler | Sorgular eşleşmez |
| 4 | `ListingVelocity` için `view_count` veri kaynağı belirsiz — `ilanlar.goruntulenme` kolonu var ama writer yok | Activity score her zaman 0 |
| 5 | `BuyerIntentExtractionService` gerçek writer'a sahip ama hiçbir event/job/controller tetiklemiyor | TalepMatch ve BuyerIntent boş |
| 6 | `BelongsToTenant` + `HasCountryScope` aynı modelde teknik olarak çalışır ama `TenantScope` auth-based değil, `TenantContextService`-based; `CountryScope` ise `Auth::user()->ulke_id` based — farklı context kaynakları | Çift scope kompozisyonu dikkatli tasarım gerektirir |
| 7 | `MarketTrendProjection` tenant bazlı olmalıdır — pazar trendleri tenant-specific ilan havuzundan türetilir | Global trend yanlış sinyal üretir |
| 8 | `proj_event_offsets` idempotency pattern'i çalışan 2 projector'da kanıtlanmış — yeni projector'lar aynı pattern'i kullanmalı | Güvenilir replay |
| 9 | `ilanlar` tablosunda `goruntulenme` (int) ve `ilan_favorileri` pivot tablosu mevcut — `ListingVelocity` için veri kaynağı olarak kullanılabilir | Yeni event/log tablosu gerekmez |
| 10 | `ilanlar` tablosunda hem `il_id` (int FK) hem `il` (string, portfolio import) kolonu var — reader'lar ikisini de kullanıyor | Standardizasyon şart |

### Önerilen Öncelik Sırası

```
Phase 1: ListingSearchProjection (en çok reader'a sahip, temel)
Phase 2: MarketTrendProjection (ListingSearch'e bağımlı, 6 reader)
Phase 3: TalepMatchProjection + BuyerIntentProjection (writer mevcut, sadece activate)
Phase 4: BuyerInterestProjection (TalepMatch'e bağımlı)
Phase 5: ListingVelocityProjection (en bağımlı, view_count kaynağı netleştir)
```

---

## 2. Mevcut Durum Analizi

### 2.1 Çalışan Projector Pattern — `ListingProjector` + `LeadProjector`

Sistemde 2 çalışan CQRS projector var. Her ikisi de aynı pattern'i takip eder:

```
Event dispatch → Listener (ShouldQueue) → proj_event_offsets check → DB::transaction → updateOrInsert → markAsProcessed
```

**ListingProjector** (`app/Listeners/ListingProjector.php`):
- Event'ler: `ListingCreated`, `ListingUpdated`
- Hedef tablo: `proj_listings`
- Queue: `projections`, Tries: 3, Backoff: [10, 30, 60]
- Idempotency: `proj_event_offsets` tablosu, `projector_name` + `event_id` unique kontrolü
- `proj_listings` tablosunda `tenant_id` **YOK** (migration'da tanımlı değil)

**LeadProjector** (`app/Listeners/LeadProjector.php`):
- Event: `LeadRegistered`
- Hedef tablo: `proj_leads_daily` (increment pattern)
- Aynı idempotency pattern

### 2.2 Boş Projection Tabloları — 6/6

| Projection | Tablo | Writer | Reader Sayısı | tenant_id | Kayıt |
|-----------|-------|--------|--------------|-----------|-------|
| ListingSearch | `listing_search_projection` | ❌ Yok | 4 | ❌ | 0 |
| ListingVelocity | `listing_velocity_projections` | Simulation | 3 | ❌ | 0 |
| MarketTrend | `market_trend_projections` | ❌ Yok | 6 | ❌ | 0 |
| BuyerInterest | `buyer_interest_projections` | ❌ Yok | 2 | ❌ | 0 |
| TalepMatch | `talep_match_projection` | Gerçek, çağrılmıyor | 3 | ❌ | 0 |
| BuyerIntent | `buyer_intent_projection` | Gerçek, çağrılmıyor | 2 | ❌ | 0 |

### 2.3 `rand()` Fallback Lokasyonları

| Dosya | Satır | Kod | Etki |
|-------|-------|-----|------|
| `DealRadarService.php` | 96 | `if ($searchFrequency === 0) $searchFrequency = rand(10, 80)` | view_count=0 → rastgele |
| `DealRadarService.php` | 97 | `if ($buyerMatchDensity === 0) $buyerMatchDensity = rand(20, 90)` | talep yok → rastgele |
| `PortfolioDoctorService.php` | 67 | `$velocity?->view_count ?? rand(10, 80)` | velocity null → rastgele |
| `PortfolioDoctorService.php` | 70 | `$matches * 5 + rand(5, 40)` | her zaman rastgele ekleme |
| `PortfolioDoctorService.php` | 86 | `rand(30, 60)` SEO score | her zaman rastgele |
| `PortfolioDoctorService.php` | 89 | `rand(40, 95)` image quality | her zaman rastgele |
| `PortfolioDoctorService.php` | 95 | `$market?->demand_index ?? rand(30, 80)` | market null → rastgele |
| `PortfolioDoctorService.php` | 98 | `rand(5, 20)` revisit signal | her zaman rastgele ekleme |

### 2.4 `city`/`district` Tip Uyumsuzluğu — Kritik

Reader'lar `city`/`district` alanlarını farklı tiplerde sorgular:

| Reader | Sorgu | Beklenen Tip | Kaynak |
|--------|-------|-------------|--------|
| `OpportunityEngineService` | `where('city', $listing->city)` | ListingSearchProjection'dan gelen | Projection'daki değer |
| `DealRadarService` | `where('city', $listing->il)` | `$listing->il` = **string** (portfolio import kolonu) | `ilanlar.il` (varchar) |
| `PortfolioDoctorService` | `where('city', $listing->il)` | `$listing->il` = **string** | `ilanlar.il` (varchar) |
| `SellerStrategyService` | `where('city', $city)` where `$city = $ilan->il_id` | **int** (il_id FK) | `ilanlar.il_id` (bigint) |
| `BuyerMatchDetectionService` | `where('city', $ilan->il?->il_adi)` | **string** (il_adi) | `iller.il_adi` |

**Sonuç:** Projection'a yazılacak `city`/`district` değerinin **string (il adı)** olması çoğu reader ile uyumludur. `SellerStrategyService` int (`il_id`) kullanır — bu reader ya projection'a `city_id` int kolonu eklenmesini ya da reader'ın `il_id` → `il_adi` join yapmasını gerektirir.

**Önerilen çözüm:** Projection'lara hem `city` (string, il adı) hem `city_id` (int, il ID) kolonu eklenir. Reader'lar ihtiyaçlarına göre kullanır. Writer her ikisini de doldurur.

---

## 3. Projection Zinciri 1 — ListingSearchProjection

### Zincir Diyagramı

```
ilanlar tablosu
    │
    ├── IlanCreated event ──→ ListingSearchProjector (YENI)
    ├── IlanUpdated event ──→ ListingSearchProjector
    ├── IlanPriceChanged event ──→ ListingSearchProjector
    ├── IlanYayinlandiEvent ──→ ListingSearchProjector
    │
    ▼
listing_search_projection (tenant_id, listing_id, title, city, city_id, district, district_id,
                           price, room_count, property_type, features, portfolio_health, seo_score)
    │
    ├── BelongsToTenant global scope (WHERE tenant_id = current)
    ├── HasCountryScope global scope (WHERE ulke_id = auth user)
    │
    ▼
Reader'lar:
    ├── OpportunityEngineService → listing_id, title, price, city, district, property_type, portfolio_health, seo_score
    ├── OpportunityScoringService → price, property_type, features, portfolio_health, seo_score
    ├── OpportunityDetectionService → all fields
    └── AIListingSearchService → city, district, price, room_count, property_type, features
    │
    ▼
Beklenen Sonuç: OpportunityEngine gerçek ilanları skorlar, rand() fallback kalkar
```

### 3.1 Kaynak Veri

**Tablo:** `ilanlar`
**Alanlar:**
- `id` → `listing_id`
- `baslik` → `title`
- `il_id` → `city_id` (int FK)
- `iller.il_adi` (join) → `city` (string)
- `ilce_id` → `district_id` (int FK)
- `ilceler.ilce_adi` (join) → `district` (string)
- `fiyat` → `price`
- `oda_sayisi` → `room_count`
- `emlak_tipi` veya `ana_kategori_id` → `property_type`
- `ozellikler` (JSON/relation) → `features`
- `completion_score` → `portfolio_health` (proxy)
- `visibility_score` → `seo_score` (proxy)

### 3.2 Writer/Event Tasarımı

**Pattern:** Event-driven listener (ListingProjector pattern)

**Tetikleyici Event'ler:**
1. `IlanCreated` — yeni ilan oluşturulduğunda insert
2. `IlanUpdated` — ilan güncellendiğinde update
3. `IlanPriceChanged` — fiyat değiştiğinde update (sadece price)
4. `IlanYayinlandiEvent` — yayın durumu değiştiğinde update

**Projector:** `ListingSearchProjector` (yeni, `ShouldQueue`)
- Queue: `projections`
- Tries: 3, Backoff: [10, 30, 60]
- Idempotency: `proj_event_offsets` pattern

**Yazma mantığı:**
```
DB::transaction:
  1. ilanlar tablosundan il + ilce join ile il_adi/ilce_adi al
  2. listing_search_projection'da updateOrInsert:
     - unique key: (tenant_id, listing_id)
     - tenant_id: ilanlar.tenant_id'den al
     - city/city_id: il_id + il_adi
     - district/district_id: ilce_id + ilce_adi
     - portfolio_health: completion_score
     - seo_score: visibility_score
  3. markAsProcessed(eventId)
```

### 3.3 Projection Schema

**Mevcut $fillable:** `listing_id, title, city, district, price, room_count, property_type, features, portfolio_health, seo_score`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable — CountryScope için)
- `city_id` (unsignedBigInteger, nullable — int FK)
- `district_id` (unsignedBigInteger, nullable — int FK)

**Unique constraint:** `UNIQUE(tenant_id, listing_id)`

### 3.4 Tenant Scope

- `BelongsToTenant` trait eklenecek → global scope `WHERE tenant_id = current_tenant`
- `HasCountryScope` zaten var → global scope `WHERE ulke_id = auth_user.ulke_id`
- İkisi birlikte çalışır (bkz. §9)

### 3.5 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Filtre |
|--------|-------|---------------|--------|
| OpportunityEngineService | `ListingSearchProjection::query()` | listing_id, title, price, city, district, property_type, portfolio_health, seo_score | city, district, property_type |
| OpportunityScoringService | `ListingSearchProjection::find($id)` | price, property_type, features, portfolio_health, seo_score | - |
| OpportunityDetectionService | `ListingSearchProjection::all()` | all | - |
| AIListingSearchService | `ListingSearchProjection::query()` | all | city, district, price, room_count, property_type, features |

### 3.6 Beklenen Sonuç

- `OpportunityEngineService::getOpportunities()` gerçek ilanları skorlar
- `portfolio_health` ve `seo_score` `ilanlar.completion_score` ve `visibility_score`'dan gelir
- `rand()` fallback kalkar (ListingSearch dolu olduğu için OpportunityEngine gerçek veri kullanır)
- Tenant izolasyonu: her tenant sadece kendi ilanlarını görür

---

## 4. Projection Zinciri 2 — ListingVelocityProjection

### Zincir Diyagramı

```
ilanlar.goruntulenme (int) + ilan_favorileri (pivot count) + talepler (count) + proj_activity_stream
    │
    ├── IlanViewed event (YENI — ilan görüntülenmesinde dispatch)
    ├── IlanFavorilendi event (YENI — favoriye eklemede dispatch)
    ├── TalepReceived event → inquiry_count increment
    ├── Scheduled job (günlük) → activity_score recalculate
    │
    ▼
listing_velocity_projections (tenant_id, listing_id, view_count, favorite_count,
                              inquiry_count, share_count, last_activity_at, activity_score)
    │
    ├── BelongsToTenant global scope
    ├── HasCountryScope global scope
    │
    ▼
Reader'lar:
    ├── DealRadarService → view_count, activity_score, favorite_count
    ├── PortfolioDoctorService → view_count, activity_score, inquiry_count
    └── SellerStrategyService → view_count, activity_score
    │
    ▼
Beklenen Sonuç: DealRadar gerçek view/activity verisi kullanır, rand(10,80) kalkar
```

### 4.1 Kaynak Veri

**Mevcut veri kaynakları:**

| Metrik | Kaynak Tablo | Kolon/Metod | Mevcut mu? |
|--------|-------------|-------------|-----------|
| `view_count` | `ilanlar` | `goruntulenme` (int, default 0) | ✅ Var |
| `favorite_count` | `ilan_favorileri` | `COUNT(*) WHERE ilan_id = X AND aktiflik_durumu = true` | ✅ Var |
| `inquiry_count` | `talepler` | `COUNT(*) WHERE ilan_id = X` (talep-ilan ilişkisi) | ✅ Var (eslesmeler pivot) |
| `share_count` | ❌ Yok | Paylaşım log tablosu mevcut değil | ❌ Yok |

**Kritik bulgu:** `ilanlar.goruntulenme` kolonu mevcut ve `default(0)`. Bu kolon ilan görüntülenme sayısını tutar. `ListingVelocityService` bunu kullanmıyor — simulation mode'da `view_count`'u hiç doldurmuyor.

**`share_count` için:** Mevcut sistemde paylaşım log tablosu yok. Phase 1'de `share_count = 0` olarak bırakılabilir. İleride `IlanPaylasildi` event'i ve log tablosu eklenebilir.

### 4.2 Writer/Event Tasarımı

**Pattern:** Hybrid (event-driven + scheduled job)

**Event-driven writer:**
1. `IlanViewed` (yeni event) → `view_count` increment
   - Tetikleyici: İlan detay sayfası görüntülenince
   - Writer: `ListingVelocityProjector::handleIlanViewed()` → `increment('view_count')` + `update(['last_activity_at' => now()])`
2. `IlanFavorilendi` (yeni event) → `favorite_count` increment
   - Tetikleyici: Kullanıcı ilanı favorilere ekleyince
3. `TalepReceived` (mevcut event) → `inquiry_count` increment
   - Tetikleyici: Yeni talep oluşturulunca

**Scheduled job (günlük):**
- `php artisan projection:sync-velocity` → tüm aktif ilanlar için `activity_score` recalculate
- `ilanlar.goruntulenme` → `listing_velocity_projections.view_count` sync (delta sync)
- `ilan_favorileri` count → `favorite_count` sync
- `activity_score` = `(view_count * 0.1) + (favorite_count * 0.3) + (inquiry_count * 0.5) + (share_count * 0.1)` (mevcut formula)

**Mevcut `ListingVelocityService::syncVelocity()` durumu:**
- Simulation mode'da çalışıyor
- `view_count`, `favorite_count`, `inquiry_count`, `share_count`'u hiç doldurmuyor (her zaman 0)
- Sadece `activity_score` ve `last_activity_at` güncelliyor
- **Çözüm:** Bu service rewrite edilerek gerçek veri kaynaklarından doldurulacak

### 4.3 Projection Schema

**Mevcut $fillable:** `listing_id, view_count, favorite_count, inquiry_count, share_count, last_activity_at, activity_score`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable)

**Unique constraint:** `UNIQUE(tenant_id, listing_id)`

### 4.4 Tenant Scope

- `BelongsToTenant` + `HasCountryScope` (diğer projection'lar ile aynı)

### 4.5 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Fallback |
|--------|-------|---------------|----------|
| DealRadarService | `ListingVelocityProjection::where('listing_id', $listing->id)->first()` | view_count, activity_score, favorite_count | `rand(10, 80)` |
| PortfolioDoctorService | `ListingVelocityProjection::where('listing_id', $listing->id)->first()` | view_count, activity_score, inquiry_count | `rand(10, 80)` |
| SellerStrategyService | `ListingVelocityProjection::where('listing_id', $ilan->id)->first()` | view_count, activity_score | 0 / 10 |

### 4.6 Beklenen Sonuç

- `DealRadarService::gatherSignals()` gerçek `view_count` ve `activity_score` kullanır
- `rand(10, 80)` fallback kalkar
- `PortfolioDoctorService` gerçek `view_count` ve `inquiry_count` kullanır
- `share_count` Phase 1'de 0 kalır (veri kaynağı yok), ileride eklenebilir

---

## 5. Projection Zinciri 3 — MarketTrendProjection

### Zincir Diyagramı

```
ilanlar tablosu (yayında olanlar, tenant-specific)
    │
    ├── Scheduled aggregation job (günlük, gece 02:00)
    │   GROUP BY tenant_id, city_id, district_id, property_type
    │   → AVG(fiyat), MEDIAN(fiyat), COUNT(*)
    │   → demand_index (talepler count / ilan count * 100)
    │   → price_change_7d / price_change_30d (önceki snapshot ile delta)
    │
    ▼
market_trend_projections (tenant_id, city, city_id, district, district_id, property_type,
                          avg_price, median_price, price_change_7d, price_change_30d,
                          demand_index, listing_count)
    │
    ├── BelongsToTenant global scope
    ├── HasCountryScope global scope
    │
    ▼
Reader'lar:
    ├── DealRadarService → demand_index, listing_count, avg_price (city + district)
    ├── DealScoringService → avg_price, median_price, demand_index (city + district)
    ├── MarketHeatService → demand_index, listing_count, price_change_7d, price_change_30d
    ├── OpportunityEngineService → avg_price, median_price, demand_index, listing_count
    ├── PortfolioDoctorService → avg_price, demand_index (city + district)
    └── SellerStrategyService → avg_price, median_price, demand_index (city + district)
    │
    ▼
Beklenen Sonuç: DealScoring gerçek pazar trendi kullanır, rand(20,90) kalkar
```

### 5.1 Kaynak Veri

**Tablo:** `ilanlar` (yayında olan ilanlar, `yayin_durumu = 'yayinda'`)

**Agregasyon mantığı:**
```sql
SELECT
    tenant_id, il_id AS city_id, ilce_id AS district_id, emlak_tipi AS property_type,
    AVG(fiyat) AS avg_price, COUNT(*) AS listing_count
FROM ilanlar
WHERE yayin_durumu = 'yayinda' AND deleted_at IS NULL
GROUP BY tenant_id, il_id, ilce_id, emlak_tipi
```

**Ek metrikler:**
- `median_price`: `fiyat`'ın median'ı (same group by)
- `demand_index`: `talepler` tablosundan aynı il+ilce'deki aktif talep sayısı / ilan sayısı * 100 (cap 100)
- `price_change_7d` / `price_change_30d`: Önceki günün/ayın snapshot'ı ile `avg_price` farkı (yüzde olarak)
- `listing_count`: Same group by'dan COUNT(*)

### 5.2 Writer/Event Tasarımı

**Pattern:** Scheduled aggregation job (event-driven değil)

**Neden scheduled job?**
- Market trend verisi tek bir event'ten değil, tüm ilan havuzunun agregasyonundan türetilir
- Günde 1 kez çalışmak yeterli (pazar trendleri gerçek zamanlı değişmez)
- Event-driven her ilan değişiminde tüm grubu recalculate etmek maliyetli

**Job:** `php artisan projection:sync-market-trends`
- Schedule: `daily at 02:00`
- Chunked processing: tenant bazında, 1000 ilan/chunk
- Idempotency: Job her çalıştığında `upsert` pattern (tüm grupları yeniden hesaplar)
- `price_change_7d` / `price_change_30d` için: önceki snapshot'ı `market_trend_projections` tablosundan okur, delta hesaplar, yeni snapshot yazar

### 5.3 Projection Schema

**Mevcut $fillable:** `city, district, property_type, avg_price, median_price, price_change_7d, price_change_30d, demand_index, listing_count`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable)
- `city_id` (unsignedBigInteger, nullable — int FK, `SellerStrategyService` için)
- `district_id` (unsignedBigInteger, nullable — int FK)
- `snapshot_date` (date — price_change hesaplaması için)

**Unique constraint:** `UNIQUE(tenant_id, city_id, district_id, property_type)`

### 5.4 Tenant Scope — MarketTrend Kararı

**KARAR: MarketTrendProjection tenant bazlı olmalıdır.**

**Gerekçe:**
- Farklı tenant'lar farklı ilan portföylerine sahiptir
- Tenant A'nın İstanbul Beylikdüzü ortalama fiyatı, Tenant B'ninkinden farklı olabilir
- Global agregasyon, küçük tenant'ların verisini büyük tenant'ların verisinde eritir
- `BelongsToTenant` global scope, her tenant'ın sadece kendi pazar trendini görmesini sağlar

**Çok-tenant paylaşım senaryosu:** Aynı il+ilce'de birden fazla tenant'ın ilanı olabilir. Bu durumda her tenant için ayrı `market_trend_projections` satırı oluşur (aynı city+district+property_type ama farklı tenant_id). Bu doğru davranıştır — her tenant kendi portföyüne göre pazar trendi görür.

### 5.5 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Tip Beklenti |
|--------|-------|---------------|-------------|
| DealRadarService | `where('city', $listing->il)->where('district', $listing->ilce)` | demand_index, listing_count, avg_price | city=string |
| DealScoringService | `where('city', ...)->where('district', ...)` | avg_price, median_price, demand_index | city=string |
| MarketHeatService | `where('city', ...)->where('district', ...)->where('property_type', ...)` | demand_index, listing_count, price_change_7d, price_change_30d | city=string |
| OpportunityEngineService | `where('city', $listing->city)->where('district', ...)->where('property_type', ...)` | avg_price, median_price, demand_index, listing_count | city=ListingSearch'dan |
| PortfolioDoctorService | `where('city', $listing->il)->where('district', $listing->ilce)` | avg_price, demand_index | city=string |
| SellerStrategyService | `where('city', $city)->where('district', $district)` where `$city = $ilan->il_id` | avg_price, median_price, demand_index | **city=int** |

**SellerStrategyService tip uyumsuzluğu:** Bu reader `$ilan->il_id` (int) kullanır. Çözüm: `city_id` (int) kolonu eklenir ve `SellerStrategyService` `where('city_id', $city)` olarak güncellenir.

### 5.6 Beklenen Sonuç

- `DealRadarService` gerçek `demand_index` ve `avg_price` kullanır
- `PortfolioDoctorService` gerçek `demand_index` kullanır, `rand(30, 80)` kalkar
- `SellerStrategyService` gerçek `median_price` ve `demand_index` kullanır
- `MarketHeatService` gerçek `price_change_7d` / `price_change_30d` kullanır
- Her tenant kendi pazar trendini görür (tenant izolasyonu)

---

## 6. Projection Zinciri 4 — BuyerInterestProjection

### Zincir Diyagramı

```
talepler tablosu + eslesmeler pivot + BuyerIntentProjection
    │
    ├── Scheduled aggregation job (günlük)
    │   Her ilan için:
    │   → candidate_count: o ilana eşleşen aktif talep sayısı
    │   → avg_match_score: eşleşme skorlarının ortalaması
    │   → top_match_score: en yüksek eşleşme skoru
    │   → high_intent_buyer_count: urgency_level >= 7 olan alıcı sayısı
    │   → recent_query_count: son 7 günde gelen talep sayısı
    │
    ▼
buyer_interest_projections (tenant_id, listing_id, candidate_count, avg_match_score,
                            top_match_score, high_intent_buyer_count, recent_query_count)
    │
    ├── BelongsToTenant global scope
    ├── HasCountryScope global scope
    │
    ▼
Reader'lar:
    ├── OpportunityEngineService → candidate_count, avg_match_score, top_match_score, high_intent_buyer_count
    └── SellerStrategyService → candidate_count, avg_match_score
    │
    ▼
Beklenen Sonuç: OpportunityEngine gerçek alıcı sinyali kullanır
```

### 6.1 Kaynak Veri

**Tablolar:**
- `talepler` — alıcı talepleri
- `eslesmeler` — talep-ilan eşleşme pivot tablosu
- `buyer_intent_projection` — alıcı niyet verisi (urgency_level için)

**Agregasyon mantığı (her ilan için):**
```sql
SELECT
    i.tenant_id, e.ilan_id AS listing_id,
    COUNT(DISTINCT e.talep_id) AS candidate_count,
    AVG(e.eslesme_skoru) AS avg_match_score,
    MAX(e.eslesme_skoru) AS top_match_score,
    COUNT(DISTINCT CASE WHEN bi.urgency_level >= 7 THEN bi.buyer_id END) AS high_intent_buyer_count,
    COUNT(DISTINCT CASE WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN t.id END) AS recent_query_count
FROM eslesmeler e
JOIN ilanlar i ON i.id = e.ilan_id
JOIN talepler t ON t.id = e.talep_id
LEFT JOIN buyer_intent_projection bi ON bi.buyer_id = t.kisi_id
WHERE e.eslesme_durumu = 'aktif'
GROUP BY i.tenant_id, e.ilan_id
```

### 6.2 Writer/Event Tasarımı

**Pattern:** Scheduled aggregation job (TalepMatch + BuyerIntent'e bağımlı)

**Neden scheduled job?**
- BuyerInterest, birden fazla projection'ın agregasyonudur (TalepMatch + BuyerIntent)
- Her talep event'inde tüm etkilenen ilanların BuyerInterest'ini recalculate etmek maliyetli
- Günde 1 kez çalışmak yeterli

**Job:** `php artisan projection:sync-buyer-interest`
- Schedule: `daily at 02:30` (MarketTrend'den sonra, TalepMatch/BuyerIntent'den sonra)
- Bağımlılık: `TalepMatchProjection` ve `BuyerIntentProjection` dolu olmalı
- Idempotency: `upsert` pattern (tüm ilanlar için yeniden hesaplar)

### 6.3 Projection Schema

**Mevcut $fillable:** `listing_id, candidate_count, avg_match_score, top_match_score, high_intent_buyer_count, recent_query_count`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable)

**Unique constraint:** `UNIQUE(tenant_id, listing_id)`

### 6.4 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Fallback |
|--------|-------|---------------|----------|
| OpportunityEngineService | `where('listing_id', $listing->listing_id)->first()` | candidate_count, avg_match_score, top_match_score, high_intent_buyer_count | null → 0 |
| SellerStrategyService | `where('listing_id', $ilan->id)` | candidate_count, avg_match_score | null → 0 |

### 6.5 Beklenen Sonuç

- `OpportunityEngineService::calculateScores()` gerçek `avg_match_score` ve `high_intent_buyer_count` kullanır
- Boş sorgu → 0 yerine gerçek `candidate_count`
- `buyerMatchScore` gerçek veriye dayanır (composite score'un %30'u)

---

## 7. Projection Zinciri 5 — TalepMatchProjection

### Zincir Diyagramı

```
talepler tablosu
    │
    ├── TalepReceived event (MEVCUT) → TalepMatchProjector (YENI listener)
    │   → BuyerIntentExtractionService::syncTalepMatch($talep) çağrısı
    │
    ▼
talep_match_projection (tenant_id, talep_id, buyer_id, city, city_id, district, district_id,
                        min_price, max_price, room_count, features, property_type,
                        purchase_intent_level)
    │
    ├── BelongsToTenant global scope
    ├── HasCountryScope global scope
    │
    ▼
Reader'lar:
    ├── BuyerMatchDetectionService → all fields (property_type, city, min_price, max_price)
    ├── PortfolioDoctorService → count() (city)
    └── SellerStrategyService → count() (city + price range)
    │
    ▼
Beklenen Sonuç: BuyerMatchDetection gerçek talep eşleşmesi yapar
```

### 7.1 Kaynak Veri

**Tablo:** `talepler`
**Alanlar:**
- `id` → `talep_id`
- `kisi_id` → `buyer_id`
- `il_id` → `city_id` (int FK)
- `iller.il_adi` (join) → `city` (string)
- `ilce_id` → `district_id` (int FK)
- `ilceler.ilce_adi` (join) → `district` (string)
- `min_fiyat` → `min_price`
- `max_fiyat` → `max_price`
- `oda_sayisi` → `room_count`
- `aranan_ozellikler_json` → `features`
- `emlak_tipi` → `property_type`
- `oncelik` → `purchase_intent_level` (calculateUrgency ile)

### 7.2 Writer/Event Tasarımı

**Pattern:** Event-driven listener (mevcut writer'ı aktive et)

**Mevcut writer:** `BuyerIntentExtractionService::syncTalepMatch(Talep $talep)` — gerçek, çalışıyor ama çağrılmıyor.

**Aktivasyon:** `TalepReceived` event'ine listener bağla

**Tetikleyici:** `TalepReceived` event'i (mevcut, `app/Events/TalepReceived.php`)
- Event zaten `Talep $talep` payload'una sahip
- Listener: `TalepMatchProjector` (yeni, `ShouldQueue`)
- Queue: `projections`
- Listener `BuyerIntentExtractionService::syncTalepMatch($event->talep)` çağırır

**Mevcut writer kodu (değişiklik yok):**
```php
// BuyerIntentExtractionService::syncTalepMatch() — zaten doğru
TalepMatchProjection::updateOrCreate(
    ['talep_id' => $talep->id],
    ['buyer_id' => $talep->kisi_id, 'city' => $talep->il?->il_adi, ...]
);
```

**Eklenecek:** `tenant_id` doldurma — `talepler.tenant_id`'den al

### 7.3 Projection Schema

**Mevcut $fillable:** `talep_id, buyer_id, city, district, min_price, max_price, room_count, features, property_type, purchase_intent_level`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable)
- `city_id` (unsignedBigInteger, nullable — int FK)
- `district_id` (unsignedBigInteger, nullable — int FK)

**Unique constraint:** `UNIQUE(tenant_id, talep_id)`

### 7.4 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Tip Beklenti |
|--------|-------|---------------|-------------|
| BuyerMatchDetectionService | `where('property_type', ...)->where('city', $ilan->il?->il_adi)->where('min_price', '<=', ...)->where('max_price', '>=', ...)` | all | city=string (il_adi) |
| PortfolioDoctorService | `where('city', $listing->il)->count()` | count | city=string |
| SellerStrategyService | `where('city', $ilan->il_id)->where('min_price', '<=', ...)->where('max_price', '>=', ...)->count()` | count | **city=int** |

**SellerStrategyService tip uyumsuzluğu:** `where('city', $ilan->il_id)` int kullanır. Çözüm: `city_id` kolonu eklenir, reader `where('city_id', $ilan->il_id)` olarak güncellenir.

### 7.5 Beklenen Sonuç

- `BuyerMatchDetectionService::getInitialCandidates()` gerçek adaylar döner
- `PortfolioDoctorService` gerçek `buyer_match_density` hesaplar
- `SellerStrategyService` gerçek `buyer_match_density` hesaplar
- Boş sonuç yerine gerçek talep eşleşmeleri

---

## 8. Projection Zinciri 6 — BuyerIntentProjection

### Zincir Diyagramı

```
kisiler tablosu + talepler tablosu
    │
    ├── TalepReceived event (MEVCUT) → BuyerIntentProjector (YENI listener)
    │   → BuyerIntentExtractionService::syncBuyerIntent($buyer) çağrısı
    │
    ▼
buyer_intent_projection (tenant_id, buyer_id, locale, preferred_city, preferred_city_id,
                         preferred_district, preferred_district_id, min_budget, max_budget,
                         property_types, room_preferences, feature_preferences,
                         urgency_level, recent_activity_score, last_contact_at)
    │
    ├── BelongsToTenant global scope
    ├── HasCountryScope global scope
    │
    ▼
Reader'lar:
    ├── BuyerMatchDetectionService → all fields (property_types JSON, preferred_city, min_budget, max_budget)
    └── BuyerMatchScoringService → urgency_level, recent_activity_score
    │
    ▼
Beklenen Sonuç: BuyerMatchScoring gerçek urgency_level kullanır
```

### 8.1 Kaynak Veri

**Tablolar:** `kisiler` + `talepler`
**Alanlar:**
- `kisi.id` → `buyer_id`
- `kisi.preferred_locale` → `locale`
- `talep.il_id` → `preferred_city_id` (int FK)
- `talep.il.il_adi` (join) → `preferred_city` (string)
- `talep.ilce_id` → `preferred_district_id` (int FK)
- `talep.ilce.ilce_adi` (join) → `preferred_district` (string)
- `talep.min_fiyat` → `min_budget`
- `talep.max_fiyat` → `max_budget`
- `talep.emlak_tipi` → `property_types` (array)
- `talep.oda_sayisi` → `room_preferences` (array)
- `talep.aranan_ozellikler_json` → `feature_preferences` (array)
- `calculateUrgency($talep)` → `urgency_level` (0-10)
- `kisi.last_contact_at` → `last_contact_at`

### 8.2 Writer/Event Tasarımı

**Pattern:** Event-driven listener (mevcut writer'ı aktive et)

**Mevcut writer:** `BuyerIntentExtractionService::syncBuyerIntent(Kisi $buyer)` — gerçek, çalışıyor ama çağrılmıyor.

**Aktivasyon:** `TalepReceived` event'ine listener bağla

**Tetikleyici:** `TalepReceived` event'i
- Listener: `BuyerIntentProjector` (yeni, `ShouldQueue`)
- Queue: `projections`
- Listener `BuyerIntentExtractionService::syncBuyerIntent($event->talep->kisi)` çağırır

**Mevcut writer kodu (değişiklik yok):**
```php
// BuyerIntentExtractionService::syncBuyerIntent() — zaten doğru
BuyerIntentProjection::updateOrCreate(
    ['buyer_id' => $buyer->id],
    $intentData
);
```

**Eklenecek:** `tenant_id` doldurma — `kisiler.tenant_id`'den al

### 8.3 Projection Schema

**Mevcut $fillable:** `buyer_id, locale, preferred_city, preferred_district, min_budget, max_budget, property_types, room_preferences, feature_preferences, urgency_level, recent_activity_score, last_contact_at`

**Eklenecek kolonlar:**
- `tenant_id` (unsignedBigInteger, nullable → backfill → NOT NULL)
- `ulke_id` (unsignedBigInteger, nullable)
- `preferred_city_id` (unsignedBigInteger, nullable — int FK)
- `preferred_district_id` (unsignedBigInteger, nullable — int FK)

**Unique constraint:** `UNIQUE(tenant_id, buyer_id)`

### 8.4 Reader Sözleşmesi

| Reader | Sorgu | Okunan Alanlar | Tip Beklenti |
|--------|-------|---------------|-------------|
| BuyerMatchDetectionService | `whereJsonContains('property_types', ...)->where('preferred_city', $ilan->il?->il_adi)->where('min_budget', '<=', ...)->where('max_budget', '>=', ...)` | all | preferred_city=string |
| BuyerMatchScoringService | `where('buyer_id', ...)` | urgency_level, recent_activity_score | - |

### 8.5 Beklenen Sonuç

- `BuyerMatchScoringService::scoreIntent()` gerçek `urgency_level` kullanır
- `BuyerMatchDetectionService` gerçek alıcı niyet profilleriyle eşleşme yapar
- Boş sonuç yerine gerçek alıcı adayları
- `urgency_level` 0 yerine gerçek değer (0-10 arası, `calculateUrgency` ile)

---

## 9. Tenant İzolasyon Stratejisi

### 9.1 `tenant_id` Kolonu Ekleme Planı

**Tüm 6 projection tablosuna `tenant_id` eklenecek.** Strateji, mevcut `2026_06_29_100000_add_tenant_id_to_core_tables.php` migration'ının pattern'ini takip eder:

```
Adım 1: Kolon ekle (nullable, indexed)
   $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');

Adım 2: Backfill (kaynak tablodan join ile)
   UPDATE listing_search_projection lsp
   JOIN ilanlar i ON i.id = lsp.listing_id
   SET lsp.tenant_id = i.tenant_id
   WHERE lsp.tenant_id IS NULL;

   UPDATE talep_match_projection tmp
   JOIN talepler t ON t.id = tmp.talep_id
   SET tmp.tenant_id = t.tenant_id
   WHERE tmp.tenant_id IS NULL;

   UPDATE buyer_intent_projection bip
   JOIN kisiler k ON k.id = bip.buyer_id
   SET bip.tenant_id = k.tenant_id
   WHERE bip.tenant_id IS NULL;

   -- ListingVelocity, BuyerInterest: ilanlar.tenant_id'den backfill
   -- MarketTrend: ilanlar.tenant_id'den backfill (city+district+type grubundan)

Adım 3: NULL kalanları default tenant'a ata (tenant_id = 1 veya SYSTEM)

Adım 4: NOT NULL constraint ekle
   $table->unsignedBigInteger('tenant_id')->nullable(false)->change();

Adım 5: Unique constraint ekle (tablo başına)
```

**Nullable → Backfill → NOT NULL sırası:** Mevcut 0 kayıt olduğu için backfill adımı boş tablolarda no-op olur. Ancak writer'lar aktive edildikten sonra yeni kayıtlar `tenant_id` ile yazılacak. Backfill, rebuild sırasında doldurulan veri için çalışacak.

### 9.2 `BelongsToTenant` + `HasCountryScope` Birlikteliği

**KISA CEVAP: Evet, aynı modelde çalışır.**

**Detaylı analiz:**

İki scope farklı context kaynaklarından çalışır ve çakışmaz:

| Scope | Context Kaynağı | Filtre | Ne Zaman Aktif |
|-------|----------------|--------|----------------|
| `TenantScope` (BelongsToTenant) | `TenantContextService::getTenant()` | `WHERE tenant_id = X` | `TenantContextService::hasTenant()` true ise |
| `CountryScope` (HasCountryScope) | `Auth::user()->ulke_id` | `WHERE ulke_id = Y` | `Auth::user()` varsa ve `ulke_id` dolu ise |

**Çalışma mantığı:**
1. `TenantScope`: `TenantContextService`'ten mevcut tenant'ı alır → `WHERE tenant_id = current_tenant`
2. `CountryScope`: `Auth::user()`'dan `ulke_id` alır → `WHERE ulke_id = user.ulke_id`
3. İki scope Eloquent query builder'a sırayla `apply` edilir → sonuç: `WHERE tenant_id = X AND ulke_id = Y`

**Öncelik sırası:** İki scope bağımsızdır. Eloquent global scope'lar registration sırasına göre uygulanır. `BelongsToTenant` trait'i `bootBelongsToTenant()` ile, `HasCountryScope` trait'i `bootHasCountryScope()` ile kayıt olur. Trait kullanım sırası (class içinde `use` sırası) boot sırasını belirler.

**Önerilen trait sırası (projection modellerinde):**
```php
class ListingSearchProjection extends BaseModel
{
    use BelongsToTenant;   // önce tenant scope
    use HasCountryScope;   // sonra country scope
}
```

**Risk: Console/job context'te Auth yok.**
- `CountryScope`: `Auth::user()` null ise scope uygulanmaz (if `$user` kontrolü var)
- `TenantScope`: `TenantContextService::hasTenant()` false ise scope uygulanmaz
- **Sonuç:** Scheduled job'lar (rebuild, sync) her iki scope'u da atlar → tüm tenant'ların verisini işler. Bu doğru davranıştır — rebuild job'ı tüm tenant'lar için çalışmalı.

**Risk: Projection writer queue context.**
- Event listener `ShouldQueue` ile queue'ya gider → worker context'inde `Auth::user()` yok
- `BelongsToTenant` trait'inin `creating` event'i `TenantContextService`'ten tenant alır → queue worker'da tenant context set edilmeli
- **Çözüm:** Event dispatch sırasında tenant_id event payload'una eklenir, projector doğrudan event'den alır (TenantContextService'e güvenmez)

### 9.3 `MarketTrendProjection` — Tenant vs. Global Kararı

**KARAR: Tenant bazlı.**

**Gerekçe:**
1. Her tenant'ın ilan portföyü farklıdır → farklı AVG/MEDIAN fiyatlar
2. Global agregasyon küçük tenant'ların verisini eritir
3. `BelongsToTenant` scope ile her tenant sadece kendi trendini görür
4. Aynı il+ilce'de birden fazla tenant varsa, her biri için ayrı satır oluşur (farklı tenant_id, aynı city+district+property_type)

**Alternatif değerlendirme (global):**
- Eğer pazar trendleri "genel Türkiye emlak piyasası" anlamında kullanılıyorsa, global olabilir
- Ancak reader'lar (`DealRadarService`, `PortfolioDoctorService`) her zaman belirli bir ilan context'inde sorgular → tenant-specific trend daha anlamlı
- `SellerStrategyService` satıcı stratejisi üretir → tenant'ın kendi portföyüne göre trend daha doğru

### 9.4 Unique Constraint'ler

| Projection | Unique Constraint | Gerekçe |
|-----------|-------------------|---------|
| `listing_search_projection` | `UNIQUE(tenant_id, listing_id)` | Her tenant'ta her ilan bir kez |
| `listing_velocity_projections` | `UNIQUE(tenant_id, listing_id)` | Her tenant'ta her ilan bir kez |
| `market_trend_projections` | `UNIQUE(tenant_id, city_id, district_id, property_type)` | Her tenant'ta her il+ilce+tip grubu bir kez |
| `buyer_interest_projections` | `UNIQUE(tenant_id, listing_id)` | Her tenant'ta her ilan bir kez |
| `talep_match_projection` | `UNIQUE(tenant_id, talep_id)` | Her tenant'ta her talep bir kez |
| `buyer_intent_projection` | `UNIQUE(tenant_id, buyer_id)` | Her tenant'ta her alıcı bir kez |

### 9.5 `ulke_id` Kolonu

Tüm projection'lara `ulke_id` (unsignedBigInteger, nullable) eklenmelidir. `HasCountryScope` trait'i `Schema::hasColumn($table, 'ulke_id')` kontrolü yapar — kolon yoksa scope uygulanmaz. Kolon eklenirse, `Auth::user()->ulke_id` ile otomatik filtre gelir.

**Backfill:** `ilanlar.ulke_id` → projection'lara join ile backfill. `ilanlar` tablosunda `country_code` (varchar, default 'TR') var ama `ulke_id` (int) migration'larla eklenmiş olmalı.

---

## 10. Writer Tasarımı

### 10.1 Writer Tipleri Özeti

| Projection | Writer Tipi | Tetikleyici | Idempotency |
|-----------|------------|------------|-------------|
| ListingSearch | Event listener | `IlanCreated`, `IlanUpdated`, `IlanPriceChanged`, `IlanYayinlandiEvent` | `proj_event_offsets` |
| ListingVelocity | Hybrid (event + scheduled) | `IlanViewed` (yeni), `IlanFavorilendi` (yeni), `TalepReceived`, günlük job | `proj_event_offsets` (event) + upsert (job) |
| MarketTrend | Scheduled job | Günlük 02:00 | Upsert (tüm grupları yeniden hesaplar) |
| BuyerInterest | Scheduled job | Günlük 02:30 | Upsert (tüm ilanları yeniden hesaplar) |
| TalepMatch | Event listener | `TalepReceived` (mevcut) | `updateOrCreate` (mevcut writer) |
| BuyerIntent | Event listener | `TalepReceived` (mevcut) | `updateOrCreate` (mevcut writer) |

### 10.2 Event-Driven Projector Pattern (ListingProjector modeli)

Yeni event-driven projector'lar mevcut `ListingProjector` pattern'ini takip eder:

```
1. Event dispatch (controller/service içinde)
2. Listener (ShouldQueue, queue=projections)
3. hasBeenProcessed(eventId) → proj_event_offsets kontrolü
4. DB::transaction:
   a. updateOrInsert / increment
   b. markAsProcessed(eventId)
5. Hata durumunda: Log::critical + throw (retry trigger)
```

**Yeni projector'lar:**

| Projector | Event | Hedef Tablo | Yazma |
|-----------|-------|------------|------|
| `ListingSearchProjector` | `IlanCreated`, `IlanUpdated`, `IlanPriceChanged`, `IlanYayinlandiEvent` | `listing_search_projection` | `updateOrInsert` |
| `ListingVelocityProjector` | `IlanViewed`, `IlanFavorilendi`, `TalepReceived` | `listing_velocity_projections` | `increment` + `update` |
| `TalepMatchProjector` | `TalepReceived` | `talep_match_projection` | `BuyerIntentExtractionService::syncTalepMatch()` |
| `BuyerIntentProjector` | `TalepReceived` | `buyer_intent_projection` | `BuyerIntentExtractionService::syncBuyerIntent()` |

### 10.3 Scheduled Job Pattern

| Job | Schedule | Bağımlılık | Yazma |
|-----|---------|-----------|------|
| `projection:sync-market-trends` | `daily at 02:00` | `ilanlar` (yayında) | Upsert (tüm gruplar) |
| `projection:sync-velocity` | `daily at 02:15` | `ilanlar.goruntulenme`, `ilan_favorileri` | Upsert (tüm aktif ilanlar) |
| `projection:sync-buyer-interest` | `daily at 02:30` | `TalepMatch` + `BuyerIntent` dolu | Upsert (tüm ilanlar) |

### 10.4 `BuyerIntentExtractionService` Aktivasyonu

**MEVCUT DURUM:** `BuyerIntentExtractionService` gerçek writer'lara sahip (`syncTalepMatch()` ve `syncBuyerIntent()`) ama hiçbir event listener, job veya controller tarafından çağrılmıyor.

**AKTİVASYON PLANI:**

1. **Event listener bağla:** `TalepReceived` event'ine iki listener ekle:
   - `TalepMatchProjector` → `BuyerIntentExtractionService::syncTalepMatch($event->talep)`
   - `BuyerIntentProjector` → `BuyerIntentExtractionService::syncBuyerIntent($event->talep->kisi)`

2. **Event dispatch kontrolü:** `TalepReceived` event'i nerede dispatch ediliyor?
   - `TalepReceived` event'i mevcut (`app/Events/TalepReceived.php`)
   - `Talep` modelinde `$dispatchesEvents` veya controller'da `event(new TalepReceived($talep))` ile dispatch edilmeli
   - **Araştırma bulgusu:** Event tanımlı ama dispatch noktası doğrulanmalı — controller/service'de `TalepReceived::dispatch($talep)` çağrısı var mı?

3. **Queue context'te tenant_id:** Event payload'una `tenant_id` eklenmeli (queue worker'da `TenantContextService` boş olabilir)

4. **Rebuild:** `php artisan projection:rebuild talep-match` ve `php artisan projection:rebuild buyer-intent` → mevcut `talepler` ve `kisiler` tablosundan backfill

**Event listener mı, job mı?**
- **Event listener (ShouldQueue):** `TalepReceived` event'i zaten mevcut. En doğal yol.
- Job değil: Çünkü writer zaten event-driven tasarlanmış (`syncTalepMatch(Talep $talep)`, `syncBuyerIntent(Kisi $buyer)` — parametre olarak model alır, job'a parametre geçmek event payload ile daha kolay)

### 10.5 Yeni Event'ler

| Event | Tetikleyici | Payload | Hedef Projector |
|-------|------------|---------|----------------|
| `IlanViewed` | İlan detay sayfası görüntülenince | `listingId, viewerId, occurredAt, eventId` | `ListingVelocityProjector` |
| `IlanFavorilendi` | Kullanıcı ilanı favorilere ekleyince | `listingId, userId, occurredAt, eventId` | `ListingVelocityProjector` |

**Mevcut event'ler (kullanılacak):**
- `IlanCreated` → `ListingSearchProjector`
- `IlanUpdated` → `ListingSearchProjector`
- `IlanPriceChanged` → `ListingSearchProjector`
- `IlanYayinlandiEvent` → `ListingSearchProjector`
- `TalepReceived` → `TalepMatchProjector` + `BuyerIntentProjector` + `ListingVelocityProjector` (inquiry_count)

---

## 11. Rebuild Stratejisi

### 11.1 Deterministic Rebuild Komutu

```
php artisan projection:rebuild {name} [--tenant=ID]
```

**{name}** parametresi hangi projection'ı rebuild edeceğini belirler:
- `listing-search` → `listing_search_projection`
- `listing-velocity` → `listing_velocity_projections`
- `market-trend` → `market_trend_projections`
- `buyer-interest` → `buyer_interest_projections`
- `talep-match` → `talep_match_projection`
- `buyer-intent` → `buyer_intent_projection`
- `all` → sıralı olarak tüm projection'lar

**`--tenant=ID`** opsiyonel: belirli bir tenant için rebuild. Verilmezse tüm tenant'lar için.

### 11.2 Backfill Mantığı — Her Projection İçin

| Projection | Backfill Kaynağı | Mantık |
|-----------|-----------------|--------|
| ListingSearch | `ilanlar` (yayında) + `iller`/`ilceler` join | Her ilan için bir satır: il_adi, ilce_adi, fiyat, emlak_tipi, completion_score, visibility_score |
| ListingVelocity | `ilanlar.goruntulenme` + `ilan_favorileri` count + `talepler` count | Her ilan için: view_count=goruntulenme, favorite_count=favori count, inquiry_count=talep count |
| MarketTrend | `ilanlar` (yayında) GROUP BY tenant+il+ilce+tip | Her grup için: AVG, MEDIAN, COUNT, demand_index |
| BuyerInterest | `eslesmeler` + `buyer_intent_projection` | Her ilan için: candidate_count, avg_match_score, high_intent_buyer_count |
| TalepMatch | `talepler` (aktif) | Her talep için: BuyerIntentExtractionService::syncTalepMatch() çağrısı |
| BuyerIntent | `kisiler` + `talepler` (aktif) | Her kisi için: BuyerIntentExtractionService::syncBuyerIntent() çağrısı |

### 11.3 Rebuild Sırası — Bağımlılık Haritası

```
Phase 1: ListingSearchProjection
    ↓ (city/district verisi buradan gelir)
Phase 2: MarketTrendProjection
    ↓ (ilanlar tablosundan agregasyon — ListingSearch'e bağımlı değil ama sıralı çalışması mantıklı)
Phase 3: TalepMatchProjection + BuyerIntentProjection (paralel)
    ↓ (BuyerInterest bunlara bağımlı)
Phase 4: BuyerInterestProjection
    ↓ (TalepMatch + BuyerIntent + eslesmeler agregasyon)
Phase 5: ListingVelocityProjection
    ↓ (ilanlar.goruntulenme + ilan_favorileri — bağımsız ama en son)
```

**Bağımlılık gerekçeleri:**
1. **ListingSearch önce:** OpportunityEngineService, ListingSearch'ten `city`/`district` alır ve MarketTrend'i bu değerlerle sorgular. ListingSearch boşsa MarketTrend sorguları eşleşmez.
2. **MarketTrend ikinci:** `ilanlar` tablosundan agregasyon yapar. ListingSearch'e bağımlı değil ama reader'lar ListingSearch + MarketTrend'i birlikte kullanır — ikisi de dolu olmalı.
3. **TalepMatch + BuyerIntent paralel:** İkisi de `TalepReceived` event'inden tetiklenir. Birbirine bağımlı değil. `BuyerIntentExtractionService` her ikisini de doldurur.
4. **BuyerInterest dördüncü:** `eslesmeler` + `buyer_intent_projection`'a bağımlı. BuyerIntent dolu olmadan `high_intent_buyer_count` hesaplanamaz.
5. **ListingVelocity son:** `ilanlar.goruntulenme` ve `ilan_favorileri`'den bağımsız ama en az reader bağımlılığı var ve `view_count` veri kaynağı netleştirilmesi en zor olan.

### 11.4 Rebuild Güvenliği

- **`withoutGlobalScope` kullanımı:** Rebuild sırasında `BelongsToTenant` ve `HasCountryScope` global scope'ları atlanmalı (tüm tenant'lar için çalışmalı)
- **Chunk processing:** Büyük tablolarda 1000 kayıt/chunk ile memory-safe işlem
- **Transaction:** Her chunk ayrı transaction'da — partial failure durumunda diğer chunk'lar etkilenmez
- **Progress logging:** Her chunk için progress log (kaç kayıt işlendi, toplam kaç kayıt)
- **Idempotent:** Rebuild komutu tekrar çalıştırılabilir — `updateOrCreate`/`upsert` pattern

---

## 12. rand() Fallback Kaldırma Planı

### 12.1 Mevcut `rand()` Çağrıları ve Değiştirme Planı

| Dosya | Satır | Mevcut Kod | Yeni Kod | Koşul |
|-------|-------|-----------|---------|-------|
| `DealRadarService.php` | 96 | `if ($searchFrequency === 0) $searchFrequency = rand(10, 80)` | `// KALDIR — searchFrequency = 0 kalır` | ListingVelocity dolu → view_count > 0 |
| `DealRadarService.php` | 97 | `if ($buyerMatchDensity === 0) $buyerMatchDensity = rand(20, 90)` | `// KALDIR — buyerMatchDensity = 0 kalır` | TalepMatch dolu → gerçek count |
| `PortfolioDoctorService.php` | 67 | `$velocity?->view_count ?? rand(10, 80)` | `$velocity?->view_count ?? 0` | ListingVelocity dolu → gerçek view_count |
| `PortfolioDoctorService.php` | 70 | `$matches * 5 + rand(5, 40)` | `$matches * 5` | TalepMatch dolu → gerçek count |
| `PortfolioDoctorService.php` | 86 | `rand(30, 60)` SEO score | `min(100, (strlen($listing->aciklama) / 20))` | Deterministic formula |
| `PortfolioDoctorService.php` | 89 | `rand(40, 95)` image quality | `min(95, $listing->fotograflar()->count() * 15)` | Deterministic formula |
| `PortfolioDoctorService.php` | 95 | `$market?->demand_index ?? rand(30, 80)` | `$market?->demand_index ?? 50` | MarketTrend dolu → gerçek demand_index |
| `PortfolioDoctorService.php` | 98 | `rand(5, 20)` revisit signal | `min(100, ($velocity?->view_count ?? 0) * 1.5)` | ListingVelocity dolu → gerçek view_count |

### 12.2 Fallback Değer Stratejisi

**Kural:** Projection boş/null olduğunda `rand()` yerine **deterministic default** kullanılır.

| Metrik | Default (projection boş) | Gerekçe |
|--------|------------------------|---------|
| `view_count` | 0 | Görüntülenme yok = 0 (rastgele değil) |
| `activity_score` | 10 | Mevcut `SellerStrategyService` default'u |
| `demand_index` | 50 | Nötr değer (ne yüksek ne düşük) |
| `buyer_match_density` | 0 | Eşleşme yok = 0 |
| `search_frequency` | 0 | Arama yok = 0 |
| `avg_price` | listing.fiyyat | Kendi fiyatı = nötr karşılaştırma |
| `median_price` | listing.fiyyat | Kendi fiyatı = nötr karşılaştırma |
| `candidate_count` | 0 | Aday yok = 0 |
| `urgency_level` | 0 | Niyet yok = 0 |

**Neden 0 veya nötr değer?**
- `rand()` her çağrıda farklı değer üretir → AI önerileri tutarsız
- 0 veya nötr değer: "veri yok" durumunu doğru temsil eder
- AI servisleri 0 değerini doğru işler (composite score'a 0 katkısı)
- Kullanıcı tutarlı sonuç görür (aynı ilan her zaman aynı skor)

### 12.3 `PortfolioDoctorService` Özel Durumu

`PortfolioDoctorService`'de `rand()` kullanan 2 metrik **projection'dan gelmez** — bunlar ilan kalitesidir:

1. **`seo_visibility_score`** (satır 86): `min(100, (strlen($listing->aciklama) / 20) + rand(30, 60))`
   - **Çözüm:** `rand(30, 60)` kaldırılır. Deterministic formula: `min(100, (strlen($listing->aciklama) / 20) + 30)`
   - Bu metrik `ListingSearchProjection.seo_score`'dan da gelebilir (ilanlar.visibility_score)

2. **`image_quality_score`** (satır 89): `rand(40, 95)`
   - **Çözüm:** `rand()` kaldırılır. Deterministic formula: `min(95, $listing->fotograflar()->count() * 15)`
   - Fotoğraf sayısına dayalı deterministic skor

Bu iki metrik projection'lardan gelmez — doğrudan `ilanlar` tablosundan hesaplanır. `rand()` kaldırıldığında deterministic formula kullanılır.

---

## 13. Idempotency Stratejisi

### 13.1 İki Idempotency Pattern'i

Sistemde iki farklı idempotency pattern'i mevcut:

| Pattern | Kullanım | Nerede | Avantaj | Dezavantaj |
|---------|---------|--------|---------|-----------|
| `proj_event_offsets` | Event-driven projector'lar | `ListingProjector`, `LeadProjector` | Event replay güvenli, duplicate event işlenmez | Ek tablo, ek sorgu |
| `updateOrCreate` | Service-based writer'lar | `BuyerIntentExtractionService` | Basit, ek tablo yok | Event replay'de duplicate yok ama "son yazan kazanır" |

### 13.2 Önerilen Strateji — Hibrit Yaklaşım

**Event-driven projector'lar için: `proj_event_offsets` pattern**

| Projector | Idempotency | Gerekçe |
|-----------|-----------|---------|
| `ListingSearchProjector` | `proj_event_offsets` | Event replay güvenli olmalı |
| `ListingVelocityProjector` | `proj_event_offsets` | `IlanViewed` event'i tekrar gelirse increment etmemeli |
| `TalepMatchProjector` | `proj_event_offsets` | `TalepReceived` replay güvenli |
| `BuyerIntentProjector` | `proj_event_offsets` | `TalepReceived` replay güvenli |

**Scheduled job'lar için: `upsert` pattern**

| Job | Idempotency | Gerekçe |
|-----|-----------|---------|
| `projection:sync-market-trends` | Upsert (tüm grupları yeniden hesaplar) | Job her çalıştığında tam yeniden hesaplama |
| `projection:sync-velocity` | Upsert (tüm ilanlar için) | Delta sync, idempotent |
| `projection:sync-buyer-interest` | Upsert (tüm ilanlar için) | Tam yeniden hesaplama |

### 13.3 `proj_event_offsets` Pattern Detayı

Mevcut çalışan pattern (`ListingProjector`):

```php
// 1. Event daha önce işlendi mi?
private function hasBeenProcessed(string $eventId): bool
{
    return DB::table('proj_event_offsets')
        ->where('projector_name', static::class)
        ->where('event_id', $eventId)
        ->exists();
}

// 2. Event işlenmediyse, transaction içinde yaz + offset kaydet
DB::transaction(function () use ($event) {
    // projection'a yaz
    DB::table('projection_table')->updateOrInsert(...);
    // offset kaydet
    $this->markAsProcessed($event->eventId);
});
```

**Yeni projector'lar aynı pattern'i kullanır:**
- `projector_name` = projector class name (unique per projector)
- `event_id` = event'in unique ID'si
- `processed_at` = işlenme zamanı

**Event ID gereksinimi:** Tüm event'lerin `eventId` property'si olmalı. Mevcut `ListingCreated`, `ListingUpdated` event'lerinde `eventId` var. Yeni event'lerde (`IlanViewed`, `IlanFavorilendi`) de `eventId` (UUID) eklenmeli.

### 13.4 `updateOrCreate` vs `proj_event_offsets` Karşılaştırması

**`updateOrCreate` (mevcut `BuyerIntentExtractionService`):**
- Avantaj: Basit, ek tablo yok
- Dezavantaj: Event replay'de "son yazan kazanır" — eski event yeni veriyi ezebilir
- Risk: `TalepReceived` event'i replay edilirse, eski talep verisi güncel veriyi ezebilir

**`proj_event_offsets` (önerilen):**
- Avantaj: Event replay güvenli — aynı event iki kez işlenmez
- Dezavantaj: Ek tablo, ek sorgu (hasBeenProcessed check)
- Güvenlik: Event sırası önemli değil, her event bir kez işlenir

**KARAR:** Yeni projector'lar `proj_event_offsets` pattern'i kullanmalı. Mevcut `BuyerIntentExtractionService`'in `updateOrCreate`'i korunabilir (çünkü `TalepReceived` event'i genellikle replay edilmez) ama yeni projector wrapper `proj_event_offsets` ile sarmalanmalı.

### 13.5 Rebuild Sırasında Idempotency

Rebuild komutu `proj_event_offsets`'i etkilemez:
- Rebuild, event'leri replay etmez — doğrudan kaynak tablodan doldurur
- Rebuild sırasında `updateOrCreate`/`upsert` kullanılır
- Rebuild sonrası yeni event'ler normal akışta `proj_event_offsets` ile işlenir
- `proj_event_offsets` tablosu rebuild sırasında temizlenmez (eski event'ler zaten işlendi)

---

## 14. Risk ve Bağımlılık Analizi

### 14.1 Risk Matrisi

| # | Risk | Olasılık | Etki | Skor | Azaltma |
|---|------|---------|------|------|---------|
| R1 | `city`/`district` tip uyumsuzluğu (string vs int) | Yüksek | Yüksek | **9/10** | Hem `city` (string) hem `city_id` (int) kolonu ekle |
| R2 | `SellerStrategyService` `il_id` (int) ile sorgular, projection `city` (string) yazar | Yüksek | Yüksek | **9/10** | `city_id` kolonu ekle, reader'ı güncelle |
| R3 | Queue worker'da `TenantContextService` boş → `tenant_id` dolmaz | Orta | Yüksek | **7/10** | Event payload'a `tenant_id` ekle |
| R4 | `ListingVelocity` `view_count` = 0 (goruntulenme hiç increment edilmemiş) | Orta | Orta | **5/10** | Rebuild'de `ilanlar.goruntulenme`'yi kaynak kullan |
| R5 | `share_count` veri kaynağı yok → her zaman 0 | Yüksek | Düşük | **3/10** | Phase 1'de 0 kalsın, ileride log tablosu ekle |
| R6 | `TalepReceived` event'i dispatch ediliyor mu? (doğrulanmalı) | Orta | Yüksek | **7/10** | Event dispatch noktasını doğrula |
| R7 | Çift scope → console/job context'te filtrelemiyor | Düşük | Orta | **4/10** | Rebuild job'ları `withoutGlobalScope` kullanır |
| R8 | `proj_event_offsets` tablosu büyür | Düşük | Düşük | **2/10** | Periyodik temizlik (90 günden eski sil) |
| R9 | `MarketTrend` rebuild — binlerce grup → memory | Düşük | Orta | **4/10** | Chunked processing, tenant bazında |
| R10 | `BuyerInterest` rebuild — `eslesmeler` boş olabilir | Orta | Orta | **5/10** | Boş ise 0 doldur, hata verme |

### 14.2 Bağımlılık Haritası

```
                    ┌──────────────────┐
                    │   ilanlar tablosu │
                    └────┬─────────────┘
           ┌─────────────┼─────────────┐
           ▼             ▼             ▼
    ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
    │ListingSearch │ │MarketTrend   │ │ListingVelocity   │
    └──────┬───────┘ └──────┬───────┘ └────────┬─────────┘
           │    ┌──────────┘                  │
           ▼    ▼                             ▼
    ┌──────────────────┐              ┌──────────────────┐
    │OpportunityEngine │              │DealRadarService  │
    │Service           │              │PortfolioDoctor   │
    └──────────────────┘              │SellerStrategy    │
                                      └──────────────────┘

    ┌──────────────┐     ┌──────────────┐
    │ talepler     │     │   kisiler    │
    └──────┬───────┘     └──────┬───────┘
           ▼                    ▼
    ┌──────────────┐     ┌──────────────┐
    │TalepMatch    │     │BuyerIntent   │
    └──────┬───────┘     └──────┬───────┘
           └────────┬──────────┘
                    ▼
           ┌──────────────┐
           │BuyerInterest │
           └──────────────┘
```

### 14.3 `city`/`district` Tip Uyumsuzluğu — Detaylı Analiz

`ilanlar` tablosunda iki set lokasyon kolonu var:
1. `il_id` (bigint, FK → `iller.id`) + `ilce_id` (bigint, FK → `ilceler.id`) — **int ID**
2. `il` (varchar, portfolio import) + `ilce` (varchar) — **string ad**

**Reader kullanımı:**

| Reader | Kullanılan kolon | Tip | Projection'da beklenen |
|--------|-----------------|-----|----------------------|
| `DealRadarService` | `$listing->il` | string | `city` = string |
| `PortfolioDoctorService` | `$listing->il` | string | `city` = string |
| `SellerStrategyService` | `$ilan->il_id` | **int** | `city` = **int** ❌ |
| `BuyerMatchDetectionService` | `$ilan->il?->il_adi` | string | `city` = string |
| `OpportunityEngineService` | `$listing->city` (ListingSearch'ten) | string | `city` = string |

**Çözüm:** Projection'lara hem `city` (string) hem `city_id` (int) eklenir. Writer her ikisini doldurur. `SellerStrategyService` `where('city_id', $ilan->il_id)` olarak güncellenir.

---

## 15. Önerilen Uygulama Sırası (Phase'ler)

### Phase 0 — Hazırlık (1-2 gün)

1. `TalepReceived` event dispatch noktasını doğrula
2. `IlanCreated`, `IlanUpdated`, `IlanPriceChanged`, `IlanYayinlandiEvent` dispatch noktalarını doğrula
3. `ilanlar.goruntulenme` kolonunun increment edildiği yeri doğrula
4. `ilan_favorileri` tablosuna kayıt ekleme noktasını doğrula

**Çıktı:** Event dispatch noktaları haritası

### Phase 1 — ListingSearchProjection (2-3 gün)

1. Migration: `tenant_id`, `ulke_id`, `city_id`, `district_id` kolonları ekle
2. Model: `BelongsToTenant` trait ekle, `$fillable` güncelle
3. Event listener: `ListingSearchProjector` (ShouldQueue, proj_event_offsets)
4. Rebuild: `php artisan projection:rebuild listing-search`
5. Reader test: `OpportunityEngineService` gerçek veri okuyor mu?

**Bağımlılık:** Yok (temel projection)
**Risk:** R1, R2 — `city_id` kolonu ile çözülür

### Phase 2 — MarketTrendProjection (2 gün)

1. Migration: `tenant_id`, `ulke_id`, `city_id`, `district_id`, `snapshot_date` ekle
2. Model: `BelongsToTenant` trait ekle
3. Scheduled job: `projection:sync-market-trends` (daily 02:00)
4. Rebuild: `php artisan projection:rebuild market-trend`
5. Reader test: `DealRadarService`, `PortfolioDoctorService` gerçek trend okuyor mu?

**Bağımlılık:** ListingSearch dolu olmalı (reader'lar birlikte kullanır)
**Risk:** R9 — chunked processing ile

### Phase 3 — TalepMatch + BuyerIntent (2 gün, paralel)

1. Migration: `tenant_id`, `ulke_id`, `city_id`, `district_id` (her iki tabloya)
2. Model: `BelongsToTenant` trait ekle (her iki model)
3. Event listener: `TalepMatchProjector` + `BuyerIntentProjector` (TalepReceived'a)
4. `BuyerIntentExtractionService` çağrısını aktive et
5. Rebuild: `php artisan projection:rebuild talep-match` + `buyer-intent`
6. Reader test: `BuyerMatchDetectionService` gerçek aday buluyor mu?

**Bağımlılık:** `TalepReceived` event dispatch (Phase 0'da doğrulanmış)
**Risk:** R6 — event dispatch noktası

### Phase 4 — BuyerInterestProjection (1-2 gün)

1. Migration: `tenant_id`, `ulke_id` ekle
2. Model: `BelongsToTenant` trait ekle
3. Scheduled job: `projection:sync-buyer-interest` (daily 02:30)
4. Rebuild: `php artisan projection:rebuild buyer-interest`
5. Reader test: `OpportunityEngineService` gerçek buyer signal okuyor mu?

**Bağımlılık:** TalepMatch + BuyerIntent dolu olmalı
**Risk:** R10 — `eslesmeler` boş ise 0 doldur

### Phase 5 — ListingVelocityProjection (2-3 gün)

1. Migration: `tenant_id`, `ulke_id` ekle
2. Model: `BelongsToTenant` trait ekle
3. Yeni event'ler: `IlanViewed`, `IlanFavorilendi` (event sınıfları + dispatch noktaları)
4. Event listener: `ListingVelocityProjector` (IlanViewed, IlanFavorilendi, TalepReceived)
5. Scheduled job: `projection:sync-velocity` (daily 02:15)
6. `ListingVelocityService::syncVelocity()` rewrite — gerçek veri kaynaklarından doldur
7. Rebuild: `php artisan projection:rebuild listing-velocity`
8. Reader test: `DealRadarService` gerçek view_count okuyor mu?

**Bağımlılık:** `ilanlar.goruntulenme` increment noktası (Phase 0'da doğrulanmış)
**Risk:** R4 — goruntulenme 0 ise 0 kalsın

### Phase 6 — rand() Kaldırma + Reader Güncelleme (1-2 gün)

1. `DealRadarService.php` — `rand()` fallback'leri kaldır (satır 96-97)
2. `PortfolioDoctorService.php` — `rand()` fallback'leri kaldır (satır 67, 70, 86, 89, 95, 98)
3. `SellerStrategyService.php` — `where('city', ...)` → `where('city_id', ...)` güncelle
4. Tüm reader'lar deterministic default kullanır
5. Entegrasyon test: tüm AI servisleri gerçek projection verisiyle çalışıyor mu?

**Bağımlılık:** Tüm projection'lar dolu olmalı (Phase 1-5 tamamlanmış)
**Risk:** Yok — sadece kod değişikliği

### Toplam Süre: 12-16 gün

---

## 16. Kabul Kriterleri — 10 Sorunun Cevapları

### Kriter 1: Her projection için `kaynak veri → writer → schema → tenant → reader → sonuç` zinciri tam tanımlanmış mı?

**CEVAP: EVET.** Raporda §3-§8 bölümlerinde her projection için tam zincir tanımlanmıştır:

| Projection | Kaynak | Writer | Schema | Tenant | Reader | Sonuç |
|-----------|--------|--------|--------|--------|--------|-------|
| ListingSearch | `ilanlar` | Event listener (IlanCreated vb.) | `listing_search_projection` | BelongsToTenant | 4 reader | Gerçek ilan skorları |
| ListingVelocity | `ilanlar.goruntulenme` + `ilan_favorileri` | Hybrid (event + job) | `listing_velocity_projections` | BelongsToTenant | 3 reader | Gerçek view/activity |
| MarketTrend | `ilanlar` (agregasyon) | Scheduled job | `market_trend_projections` | BelongsToTenant | 6 reader | Gerçek pazar trendi |
| BuyerInterest | `eslesmeler` + `buyer_intent` | Scheduled job | `buyer_interest_projections` | BelongsToTenant | 2 reader | Gerçek alıcı sinyali |
| TalepMatch | `talepler` | Event listener (TalepReceived) | `talep_match_projection` | BelongsToTenant | 3 reader | Gerçek talep eşleşme |
| BuyerIntent | `kisiler` + `talepler` | Event listener (TalepReceived) | `buyer_intent_projection` | BelongsToTenant | 2 reader | Gerçek urgency_level |

### Kriter 2: `tenant_id` kolonu hangi tablolara, nasıl (nullable mı NOT NULL mı) eklenecek?

**CEVAP:** Tüm 6 projection tablosuna eklenecek. Strateji:

1. **Kolon ekle:** `unsignedBigInteger('tenant_id')->nullable()->index()` — nullable olarak başlar
2. **Backfill:** Kaynak tablodan join ile doldur (ilanlar.tenant_id, talepler.tenant_id, kisiler.tenant_id)
3. **NOT NULL:** Backfill sonrası `->nullable(false)->change()` ile NOT NULL yap
4. **Unique constraint:** Tablo başına unique key ekle (örn. `UNIQUE(tenant_id, listing_id)`)

**Tablolar:**
- `listing_search_projection` → `tenant_id` (nullable → backfill → NOT NULL)
- `listing_velocity_projections` → `tenant_id` (nullable → backfill → NOT NULL)
- `market_trend_projections` → `tenant_id` (nullable → backfill → NOT NULL)
- `buyer_interest_projections` → `tenant_id` (nullable → backfill → NOT NULL)
- `talep_match_projection` → `tenant_id` (nullable → backfill → NOT NULL)
- `buyer_intent_projection` → `tenant_id` (nullable → backfill → NOT NULL)

**Ek olarak:** Tüm tablolara `ulke_id` (nullable) eklenecek — `HasCountryScope` için.

### Kriter 3: `BelongsToTenant` ve `HasCountryScope` aynı modelde çalışır mı?

**CEVAP: EVET, çalışır.** İki scope farklı context kaynaklarından çalışır:

- `TenantScope` (BelongsToTenant): `TenantContextService::getTenant()` → `WHERE tenant_id = X`
- `CountryScope` (HasCountryScope): `Auth::user()->ulke_id` → `WHERE ulke_id = Y`

İki scope Eloquent query builder'a sırayla `apply` edilir → sonuç: `WHERE tenant_id = X AND ulke_id = Y`. Çakışmazlar.

**Dikkat edilmesi gerekenler:**
- Console/job context'te `Auth::user()` yok → `CountryScope` uygulanmaz (if `$user` kontrolü var)
- Queue worker'da `TenantContextService` boş olabilir → event payload'a `tenant_id` eklenmeli
- Rebuild job'ları her iki scope'u da atlamalı (`withoutGlobalScope`)

### Kriter 4: `MarketTrendProjection` tenant bazlı mı, global mi?

**CEVAP: Tenant bazlı olmalıdır.**

**Gerekçe:**
- Her tenant'ın ilan portföyü farklıdır → farklı AVG/MEDIAN fiyatlar
- Global agregasyon küçük tenant'ların verisini eritir
- Reader'lar (`DealRadarService`, `PortfolioDoctorService`) belirli ilan context'inde sorgular → tenant-specific trend daha anlamlı
- `BelongsToTenant` scope ile her tenant sadece kendi trendini görür
- Aynı il+ilce'de birden fazla tenant varsa, her biri için ayrı satır oluşur (farklı tenant_id)

### Kriter 5: `city`/`district` alanları string mi, int mi olmalı? Reader'lar hangisini bekliyor?

**CEVAP: Hem string hem int olmalı — her ikisi de eklenecek.**

**Mevcut durum:** Reader'lar karışık kullanıyor:
- 4 reader string bekler (`$listing->il`, `$ilan->il?->il_adi` — il adı)
- 1 reader int bekler (`SellerStrategyService` — `$ilan->il_id`)

**Çözüm:**
- `city` (string, il adı) — çoğu reader bunu kullanır
- `city_id` (int, il ID) — `SellerStrategyService` bunu kullanır
- `district` (string) + `district_id` (int) — aynı pattern
- Writer her ikisini de doldurur (il_id + il_adi join ile)
- `SellerStrategyService` `where('city_id', $ilan->il_id)` olarak güncellenir

### Kriter 6: `ListingVelocity` için `view_count` verisi nereden gelecek? Yeni event mi, mevcut tablo mu?

**CEVAP: Mevcut tablo + yeni event (hibrit).**

**Mevcut veri kaynağı:** `ilanlar.goruntulenme` (unsignedBigInteger, default 0) — ilan görüntülenme sayısını tutar. Mevcut tablo, yeni log tablosu gerekmez.

**Ek veri kaynakları:**
- `favorite_count` → `ilan_favorileri` pivot tablosundan COUNT
- `inquiry_count` → `talepler` tablosundan COUNT (eslesmeler üzerinden)
- `share_count` → Veri kaynağı YOK — Phase 1'de 0 kalır

**Writer stratejisi (hibrit):**
1. **Event-driven:** `IlanViewed` (yeni event) → `view_count` increment — ilan detay sayfası görüntülenince dispatch
2. **Event-driven:** `IlanFavorilendi` (yeni event) → `favorite_count` increment
3. **Event-driven:** `TalepReceived` (mevcut event) → `inquiry_count` increment
4. **Scheduled job:** `projection:sync-velocity` (günlük) → `ilanlar.goruntulenme`'den delta sync + `activity_score` recalculate

**Yeni event mi, mevcut tablo mu?** İkisi birden: event-driven real-time increment + scheduled job delta sync (mevcut `ilanlar.goruntulenme`'den).

### Kriter 7: `BuyerIntentExtractionService` nasıl aktive edilecek? Event listener mı, job mı?

**CEVAP: Event listener (ShouldQueue).**

**Gerekçe:**
- `TalepReceived` event'i zaten mevcut (`app/Events/TalepReceived.php`)
- `BuyerIntentExtractionService`'in `syncTalepMatch(Talep $talep)` ve `syncBuyerIntent(Kisi $buyer)` metodları zaten model parametre alır — event payload ile doğrudan uyumlu
- Job değil: Çünkü writer event-driven tasarlanmış, event listener en doğal entegrasyon

**Aktivasyon planı:**
1. `TalepMatchProjector` (yeni listener, ShouldQueue) → `BuyerIntentExtractionService::syncTalepMatch($event->talep)`
2. `BuyerIntentProjector` (yeni listener, ShouldQueue) → `BuyerIntentExtractionService::syncBuyerIntent($event->talep->kisi)`
3. Her iki listener `TalepReceived` event'ine bağlanır
4. Queue: `projections`, Tries: 3, Backoff: [10, 30, 60]
5. Idempotency: `proj_event_offsets` pattern

**Önkoşul:** `TalepReceived` event'inin dispatch edildiği doğrulanmalı (Phase 0).

### Kriter 8: Rebuild sırası ne? Hangi projection önce?

**CEVAP:**

```
Phase 1: ListingSearchProjection (temel, en çok reader)
Phase 2: MarketTrendProjection (ilanlar agregasyonu)
Phase 3: TalepMatchProjection + BuyerIntentProjection (paralel)
Phase 4: BuyerInterestProjection (TalepMatch + BuyerIntent'e bağımlı)
Phase 5: ListingVelocityProjection (en bağımlı)
```

**Sıralama gerekçesi:**
1. ListingSearch önce — OpportunityEngine, ListingSearch'ten `city`/`district` alır ve MarketTrend'i bu değerlerle sorgular
2. MarketTrend ikinci — ListingSearch ile birlikte kullanılır, ikisi de dolu olmalı
3. TalepMatch + BuyerIntent paralel — birbirine bağımlı değil, aynı event'ten tetiklenir
4. BuyerInterest dördüncü — `buyer_intent_projection`'a bağımlı (`high_intent_buyer_count` için)
5. ListingVelocity son — `ilanlar.goruntulenme`'den bağımsız ama en az reader bağımlılığı

### Kriter 9: `rand()` fallback'ler ne ile değiştirilecek?

**CEVAP: Deterministic default değerler ile.**

| Dosya | Mevcut | Yeni | Gerekçe |
|-------|--------|------|---------|
| DealRadarService:96 | `rand(10, 80)` | `0` (kalsın) | view_count=0 → searchFrequency=0 |
| DealRadarService:97 | `rand(20, 90)` | `0` (kalsın) | talep yok → buyerMatchDensity=0 |
| PortfolioDoctor:67 | `rand(10, 80)` | `0` | velocity null → view_count=0 |
| PortfolioDoctor:70 | `rand(5, 40)` | kaldır | sadece `$matches * 5` |
| PortfolioDoctor:86 | `rand(30, 60)` | `+ 30` (sabit) | deterministic SEO formula |
| PortfolioDoctor:89 | `rand(40, 95)` | `min(95, fotoğraf sayısı * 15)` | deterministic image score |
| PortfolioDoctor:95 | `rand(30, 80)` | `50` | nötr demand_index default |
| PortfolioDoctor:98 | `rand(5, 20)` | kaldır | sadece `view_count * 1.5` |

**Kural:** `rand()` → deterministic default (0 veya nötr değer). AI önerileri tutarlı olur.

### Kriter 10: Idempotency stratejisi ne? `proj_event_offsets` pattern mi, `updateOrCreate` mi?

**CEVAP: Hibrit — event-driven projector'lar `proj_event_offsets`, scheduled job'lar `upsert`.**

| Writer Tipi | Idempotency | Nerede |
|------------|-----------|--------|
| Event-driven projector'lar | `proj_event_offsets` | ListingSearchProjector, ListingVelocityProjector, TalepMatchProjector, BuyerIntentProjector |
| Scheduled job'lar | `upsert` (tüm grupları yeniden hesaplar) | sync-market-trends, sync-velocity, sync-buyer-interest |

**`proj_event_offsets` pattern (event-driven):**
- `projector_name` + `event_id` unique kontrolü
- Event replay güvenli — aynı event iki kez işlenmez
- Transaction içinde: projection yaz + offset kaydet

**`upsert` pattern (scheduled job):**
- Job her çalıştığında tam yeniden hesaplama
- `updateOrCreate` / `upsert` ile idempotent
- Eski veriyi ezer, yeni veriyi yazar

**Mevcut `BuyerIntentExtractionService`'in `updateOrCreate`'i:** Korunabilir ama yeni projector wrapper `proj_event_offsets` ile sarmalanmalı (event replay güvenliği için).

---

## 17. Ek: Reader Sözleşme Matrisi

### 17.1 Tam Reader → Projection → Alan Matrisi

| Reader | Projection | Sorgu Patterni | Okunan Alanlar | Tip |
|--------|-----------|---------------|---------------|-----|
| OpportunityEngineService | ListingSearch | `::query()->select([...])` | listing_id, title, price, city, district, property_type, portfolio_health, seo_score | string |
| OpportunityEngineService | BuyerInterest | `where('listing_id', ...)->first()` | candidate_count, avg_match_score, top_match_score, high_intent_buyer_count | - |
| OpportunityEngineService | MarketTrend | `where('city', ...)->where('district', ...)->where('property_type', ...)->first()` | avg_price, median_price, demand_index, listing_count | string |
| DealRadarService | ListingVelocity | `where('listing_id', ...)->first()` | view_count, activity_score, favorite_count | - |
| DealRadarService | MarketTrend | `where('city', $listing->il)->where('district', $listing->ilce)->first()` | demand_index, listing_count, avg_price | string |
| PortfolioDoctorService | ListingVelocity | `where('listing_id', ...)->first()` | view_count, activity_score, inquiry_count | - |
| PortfolioDoctorService | MarketTrend | `where('city', $listing->il)->where('district', $listing->ilce)->first()` | avg_price, demand_index | string |
| PortfolioDoctorService | TalepMatch | `where('city', $listing->il)->count()` | count | string |
| SellerStrategyService | MarketTrend | `where('city', $ilan->il_id)->where('district', $ilan->ilce_id)->first()` | avg_price, median_price, demand_index | **int** |
| SellerStrategyService | ListingVelocity | `where('listing_id', ...)->first()` | view_count, activity_score | - |
| SellerStrategyService | TalepMatch | `where('city', $ilan->il_id)->where('min_price', '<=', ...)->where('max_price', '>=', ...)->count()` | count | **int** |
| SellerStrategyService | BuyerInterest | `where('listing_id', ...)` | candidate_count, avg_match_score | - |
| BuyerMatchDetectionService | TalepMatch | `where('property_type', ...)->where('city', $ilan->il?->il_adi)->where('min_price', ...)->where('max_price', ...)->get()` | all | string |
| BuyerMatchDetectionService | BuyerIntent | `whereJsonContains('property_types', ...)->where('preferred_city', $ilan->il?->il_adi)->where('min_budget', ...)->where('max_budget', ...)->get()` | all | string |
| BuyerMatchScoringService | BuyerIntent | `where('buyer_id', ...)` | urgency_level, recent_activity_score | - |
| OpportunityScoringService | ListingSearch | `find($id)` | price, property_type, features, portfolio_health, seo_score | - |
| OpportunityDetectionService | ListingSearch | `all()` | all | - |

### 17.2 Mevcut Event Envanteri

| Event | Dosya | Payload | Dispatch Noktası | Kullanım |
|-------|-------|---------|-----------------|---------|
| `ListingCreated` | `app/Events/ListingCreated.php` | listingId, title, yayinDurumu, price, currencyId, ownerId, categoryId, cityId, occurredAt, eventId | Doğrulanmalı | ListingProjector (mevcut) |
| `ListingUpdated` | `app/Events/ListingUpdated.php` | listingId, title, yayinDurumu, price, ..., priceChanged, yayinDurumuChanged, eventId | Doğrulanmalı | ListingProjector (mevcut) |
| `IlanCreated` | `app/Events/IlanCreated.php` | - | Doğrulanmalı | ListingSearchProjector (yeni) |
| `IlanUpdated` | `app/Events/IlanUpdated.php` | - | Doğrulanmalı | ListingSearchProjector (yeni) |
| `IlanPriceChanged` | `app/Events/IlanPriceChanged.php` | - | Doğrulanmalı | ListingSearchProjector (yeni) |
| `IlanYayinlandiEvent` | `app/Events/IlanYayinlandiEvent.php` | - | Doğrulanmalı | ListingSearchProjector (yeni) |
| `TalepReceived` | `app/Events/TalepReceived.php` | Talep $talep | Doğrulanmalı | TalepMatchProjector + BuyerIntentProjector (yeni) |
| `LeadRegistered` | `app/Events/LeadRegistered.php` | - | Mevcut | LeadProjector (mevcut) |
| `LeadOlusturuldu` | `app/Events/LeadOlusturuldu.php` | - | Doğrulanmalı | - |
| `IlanViewed` | **YENİ** | listingId, viewerId, occurredAt, eventId | İlan detay controller | ListingVelocityProjector (yeni) |
| `IlanFavorilendi` | **YENİ** | listingId, userId, occurredAt, eventId | Favori controller | ListingVelocityProjector (yeni) |

### 17.3 Mevcut Veri Kaynağı Tabloları

| Tablo | Kolon | Tip | Kullanım |
|-------|-------|-----|---------|
| `ilanlar` | `goruntulenme` | unsignedBigInteger (default 0) | ListingVelocity view_count kaynağı |
| `ilanlar` | `il_id` | unsignedBigInteger (FK) | ListingSearch city_id, MarketTrend city_id |
| `ilanlar` | `ilce_id` | unsignedBigInteger (FK) | ListingSearch district_id, MarketTrend district_id |
| `ilanlar` | `fiyat` | decimal(15,2) | ListingSearch price, MarketTrend avg_price |
| `ilanlar` | `emlak_tipi` | string | ListingSearch property_type, MarketTrend property_type |
| `ilanlar` | `completion_score` | tinyInteger | ListingSearch portfolio_health |
| `ilanlar` | `visibility_score` | integer | ListingSearch seo_score |
| `ilanlar` | `tenant_id` | unsignedBigInteger (nullable) | Tüm projection'lar tenant_id backfill |
| `ilan_favorileri` | pivot (ilan_id, user_id) | - | ListingVelocity favorite_count |
| `talepler` | `kisi_id` | foreignId | TalepMatch buyer_id, BuyerIntent buyer_id |
| `talepler` | `il_id`, `ilce_id` | unsignedBigInteger | TalepMatch city_id/district_id |
| `talepler` | `min_fiyat`, `max_fiyat` | decimal | TalepMatch min_price/max_price |
| `talepler` | `emlak_tipi` | string | TalepMatch property_type |
| `talepler` | `oncelik` | string | TalepMatch purchase_intent_level (calculateUrgency) |
| `talepler` | `tenant_id` | unsignedBigInteger (nullable) | TalepMatch tenant_id backfill |
| `kisiler` | `tenant_id` | unsignedBigInteger (nullable) | BuyerIntent tenant_id backfill |
| `eslesmeler` | pivot (talep_id, ilan_id) | - | BuyerInterest candidate_count |

---

> **Rapor Sonu — İlk Versiyon.** Aşağıdaki §18-§20 bölümleri reviewer geri bildirimi üzerine eklenmiştir.

---

## 18. tenant_id Backfill — Tenant Kaynağı ve Çözülemeyen Kayıt Politikası

### 18.1 Tenant Kaynağı Hiyerarşisi

Mevcut `2026_06_29_100000_add_tenant_id_to_core_tables.php` migration'ı, `ilanlar.tenant_id`'yi `users.tenant_id`'den backfill eder:

```sql
UPDATE ilanlar SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = ilanlar.danisman_id) WHERE tenant_id IS NULL
```

Projection'lar için backfill, kaynak tablonun `tenant_id`'sini takip eder. Her projection için tenant kaynağı farklıdır:

| Projection | Tenant Kaynağı | Join Yolu | SQL |
|-----------|---------------|-----------|-----|
| `listing_search_projection` | `ilanlar.tenant_id` | `JOIN ilanlar i ON i.id = lsp.listing_id` | `SET lsp.tenant_id = i.tenant_id` |
| `listing_velocity_projections` | `ilanlar.tenant_id` | `JOIN ilanlar i ON i.id = lvp.listing_id` | `SET lvp.tenant_id = i.tenant_id` |
| `market_trend_projections` | `ilanlar.tenant_id` | Grup bazında: aynı `tenant_id + city_id + district_id + property_type` grubundan | Agregasyon sırasında `tenant_id` GROUP BY'a dahil |
| `buyer_interest_projections` | `ilanlar.tenant_id` | `JOIN ilanlar i ON i.id = bip.listing_id` | `SET bip.tenant_id = i.tenant_id` |
| `talep_match_projection` | `talepler.tenant_id` | `JOIN talepler t ON t.id = tmp.talep_id` | `SET tmp.tenant_id = t.tenant_id` |
| `buyer_intent_projection` | `kisiler.tenant_id` | `JOIN kisiler k ON k.id = bip.buyer_id` | `SET bip.tenant_id = k.tenant_id` |

### 18.2 Çözülemeyen Kayıt Politikası (Unresolved Record Policy)

Backfill sırasında `tenant_id = NULL` kalan kayıtlar için üç senaryo vardır:

**Senaryo A — Kaynak tabloda `tenant_id` NULL:**
- `ilanlar.tenant_id` NULL ise (danışman silinmiş veya tenant atanmamış)
- `talepler.tenant_id` NULL ise (danışman silinmiş)
- `kisiler.tenant_id` NULL ise (danışman silinmiş)

**Senaryo B — Join bulunamadı (orphan record):**
- `listing_search_projection.listing_id` → `ilanlar`'da yok (silinmiş ilan)
- `talep_match_projection.talep_id` → `talepler`'de yok (silinmiş talep)
- `buyer_intent_projection.buyer_id` → `kisiler`'de yok (silinmiş kişi)

**Senaryo C — Projection boş (0 kayıt):**
- Mevcut durum: 6/6 projection 0 kayıt → backfill no-op

### 18.3 Çözüm Politikası

```
Adım 1: Backfill (join ile)
   → Çözülen kayıtlar: tenant_id dolu

Adım 2: Kalan NULL'ları tespit et
   SELECT COUNT(*) FROM listing_search_projection WHERE tenant_id IS NULL;
   → Log: "X kayıt tenant_id NULL (kaynak tabloda tenant yok)"

Adım 3: NULL kayıtlar için karar:
   → Senaryo A (kaynak tenant NULL): tenant_id = 1 (SYSTEM/default tenant)
      Gerekçe: Bu kayıtlar sistem öncesi veridir, default tenant'a atanır
      Risk: Default tenant'ın projection'ları şişer — kabul edilebilir (geçici)

   → Senaryo B (orphan record): SİL
      Gerekçe: Kaynak tabloda karşılığı yok → projection'da tutmak anlamsız
      SQL: DELETE FROM listing_search_projection WHERE tenant_id IS NULL AND listing_id NOT IN (SELECT id FROM ilanlar)

   → Senaryo C (projection boş): No-op, rebuild sırasında writer tenant_id ile yazar

Adım 4: NOT NULL constraint
   → Tüm NULL'lar çözüldükten sonra: ALTER TABLE ... MODIFY tenant_id NOT NULL

Adım 5: Rapor
   → Migration çıktısı: "X kayıt backfill edildi, Y kayıt default tenant'a atandı, Z orphan silindi"
```

### 18.4 Backfill Güvenliği

- **Dry-run mode:** `php artisan projection:backfill-tenant --dry-run` → sadece rapor üretir, yazmaz
- **Tenant bazında:** `--tenant=ID` ile belirli tenant için backfill
- **Rollback:** Backfill migration'ı `down()` metodunda `tenant_id`'yi NULL'lar (geri al)
- **Audit log:** Backfill sırasında hangi kayıtların hangi tenant'a atandığı loglanır

---

## 19. MarketTrend Tenant-Bazlı Kararı — Ürün/Mimari Karar Rasyoneli

### 19.1 Karar

**MarketTrendProjection tenant bazlı olacaktır.** Her tenant kendi ilan portföyünden türetilen pazar trendini görür.

### 19.2 Ürün Gerekçesi

YALIHAN OS çoklu-tenant (multi-tenant) bir SaaS platformudur. Her tenant (emlak ofisi/franchise) kendi portföyünü yönetir. Pazar trendi, bir tenant'ın karar verme sürecinde şu amaçlarla kullanılır:

1. **Fiyat stratejisi:** "Bu ilan bölge ortalamasında mı?" — tenant'ın kendi portföyündü ortalama fiyat anlamlıdır, rakip tenant'ların portföyü anlamlı değildir
2. **Talep analizi:** "Bu bölgede talep yüksek mi?" — tenant'ın kendi talep havuzu (talepler tablosu) anlamlıdır
3. **Satıcı danışmanlığı:** "Fiyat düşürmeli mi?" — tenant'ın kendi portföyüne göre karşılaştırma yapılmalıdır

**Global trend ne zaman anlamlı olur?** Eğer platform bir "pazar araştırma" aracı sunuyorsa (örn. "İstanbul genelinde ortalama daire fiyatı"), global trend anlamlıdır. Ancak mevcut reader'ların hiçbiri bu kullanımı hedeflemiyor — hepsi belirli bir ilan context'inde sorgular.

### 19.3 Mimari Gerekçe

1. **Veri izolasyonu:** `BelongsToTenant` global scope, her tenant'ın sadece kendi trendini görmesini garanti eder. Global trend, tenant izolasyonunu kırar.
2. **Agregasyon doğruluğu:** Tenant A'nın 10 ilanı, Tenant B'nin 1000 ilanı varsa, global ortalama Tenant B'ye kayar. Tenant A'nın kararları yanlış olur.
3. **Unique constraint:** `UNIQUE(tenant_id, city_id, district_id, property_type)` — her tenant için ayrı grup. Global olsaydı `UNIQUE(city_id, district_id, property_type)` olurdu, ama tenant izolasyonu çalışmazdı.
4. **Rebuild:** Rebuild job'ı tenant bazında çalışır. Global rebuild, tüm tenant'ların verisini tek tabloda eritir.

### 19.4 Alternatif: Hibrit Yaklaşım (İleride)

İleride "global pazar trendi" özelliği eklenirse:
- `market_trend_projections` tablosu tenant bazlı kalır
- Yeni `global_market_trend_projections` tablosu eklenir (tenant_id = NULL, global agregasyon)
- Reader'lar ihtiyaca göre tenant-specific veya global trend'i sorgular
- Bu, mevcut tasarımı bozmaz

### 19.5 Karar Özeti

| Boyut | Karar | Gerekçe |
|-------|-------|---------|
| Scope | Tenant bazlı | Çoklu-tenant izolasyonu |
| Unique key | `(tenant_id, city_id, district_id, property_type)` | Her tenant ayrı grup |
| Global trend | Yok (ileride ayrı tablo) | Mevcut reader'lar tenant-specific |
| Rebuild | Tenant bazında | İzolasyon korunmalı |

---

## 20. rand() → Confidence/Status Sözleşmesi

### 20.1 Sorun

`rand()` fallback'leri sadece `0` ile değiştirmek yeterli değildir. `0` değeri "veri yok" ile "veri var ama 0" arasındaki farkı belirtmez. Reader'lar ve UI, bir projection'dan gelen `0`'nın gerçek bir sıfır mı yoksa "veri henüz doldurulmamış" mı olduğunu ayırt edemez.

### 20.2 Confidence/Status Sözleşmesi

Her projection'a bir `data_status` kolonu eklenir (varchar, default `'stale'`):

| Status | Anlam | Reader Davranışı |
|--------|-------|-----------------|
| `fresh` | Projection dolu, son 24 saat içinde güncellenmiş | Tam güven, gerçek veriyi kullan |
| `stale` | Projection dolu ama 24 saatten eski | Kullan ama uyarı ver (UI'da "trend verisi güncel değil") |
| `empty` | Projection boş (0 kayıt) | Default değer kullan, UI'da "veri yok" göster |
| `partial` | Projection kısmen dolu (bazı alanlar 0) | Mevcut alanları kullan, eksik alanlar için default |

### 20.3 Projection'lara Eklenecek Kolonlar

| Projection | Yeni Kolon | Tip | Default | Yazan |
|-----------|-----------|-----|---------|-------|
| `listing_search_projection` | `data_status` | varchar(20) | `'empty'` | Rebuild job / writer |
| `listing_velocity_projections` | `data_status` | varchar(20) | `'empty'` | Sync job / event writer |
| `market_trend_projections` | `data_status` | varchar(20) | `'empty'` | Sync job |
| `buyer_interest_projections` | `data_status` | varchar(20) | `'empty'` | Sync job |
| `talep_match_projection` | `data_status` | varchar(20) | `'empty'` | Event writer |
| `buyer_intent_projection` | `data_status` | varchar(20) | `'empty'` | Event writer |

### 20.4 Reader Davranışı — Yeni Sözleşme

```php
// DealRadarService::gatherSignals() — ESKİ
$searchFrequency = min(100, ($velocity?->view_count ?? 0) / 10);
if ($searchFrequency === 0) $searchFrequency = rand(10, 80); // ❌ KALDIR

// DealRadarService::gatherSignals() — YENİ
$velocity = ListingVelocityProjection::where('listing_id', $listing->id)->first();
$searchFrequency = min(100, ($velocity?->view_count ?? 0) / 10);

// data_status bazlı confidence
if ($velocity?->data_status === 'empty' || !$velocity) {
    $searchFrequency = 0;
    $signalsConfidence['search_frequency'] = 'no_data'; // UI'a "veri yok" sinyali
} else {
    $signalsConfidence['search_frequency'] = 'measured';
}
```

### 20.5 UI'ya Taşınan Sözleşme

AI servisleri, reader'lardan gelen her metrik için bir `confidence` alanı döner:

```json
{
  "deal_score": 72.5,
  "deal_tier": "FAST_MOVING",
  "signal_breakdown": {
    "search_frequency": 45,
    "buyer_match_density": 30
  },
  "confidence": {
    "search_frequency": "measured",
    "buyer_match_density": "no_data",
    "listing_view_velocity": "measured",
    "price_advantage_score": "measured",
    "market_demand_score": "stale"
  },
  "data_quality": "partial"
}
```

UI, `confidence` alanına göre:
- `measured` → normal gösterim
- `no_data` → "Bu metrik için veri henüz toplanmıyor" rozeti
- `stale` → "Veriler 24+ saat önce güncellendi" uyarısı
- `partial` → genel "Bazı metrikler tahminidir" notu

### 20.6 Fallback Değer Matrisi (Güncellenmiş)

| Metrik | `empty` (veri yok) | `stale` (eski) | `partial` (eksik) | `fresh` (güncel) |
|--------|-------------------|---------------|------------------|-----------------|
| `view_count` | 0 | mevcut değer | mevcut değer | mevcut değer |
| `activity_score` | 0 | mevcut değer | mevcut değer | mevcut değer |
| `demand_index` | 50 (nötr) | mevcut değer | mevcut değer | mevcut değer |
| `avg_price` | listing.fiyat | mevcut değer | mevcut değer | mevcut değer |
| `buyer_match_density` | 0 | mevcut değer | mevcut değer | mevcut değer |
| `candidate_count` | 0 | mevcut değer | mevcut değer | mevcut değer |
| `urgency_level` | 0 | mevcut değer | mevcut değer | mevcut değer |

**Kural:** `empty` → deterministic default (0 veya nötr), `stale`/`partial`/`fresh` → mevcut değer. `rand()` hiçbir durumda kullanılmaz.

### 20.7 data_status Yazma Mantığı

| Writer | data_status Yazma |
|--------|------------------|
| Rebuild job | `fresh` (yeni doldu) |
| Event-driven projector | `fresh` (event geldi, güncel) |
| Scheduled sync job | `fresh` (job çalıştı, güncel) |
| 24 saat geçti (job çalışmadı) | `stale` (scheduled job tarafından güncellenir) |
| Projection boş (0 kayıt) | `empty` (default, hiç yazılmamış) |

**Scheduled job, `data_status`'ü de günceller:**
```php
// projection:sync-market-trends job
foreach ($groups as $group) {
    MarketTrendProjection::updateOrCreate(
        ['tenant_id' => ..., 'city_id' => ..., 'district_id' => ..., 'property_type' => ...],
        ['avg_price' => ..., 'data_status' => 'fresh', 'updated_at' => now()]
    );
}
// 24 saatten eski kayıtları stale yap
MarketTrendProjection::where('updated_at', '<', now()->subDay())
    ->where('data_status', 'fresh')
    ->update(['data_status' => 'stale']);
```

---

## 21. İlk Kod Paketi — Tek Projection Tenant Migration + Negatif Test

### 21.1 Reviewer Talimatı

> "İlk gerçek kod paketi yalnız **tek projection için tenant migration + negatif tenant testi** olmalı. 6 tabloyu aynı anda değiştirmek riski artırır."

### 21.2 İlk Kod Paketi Kapsamı

**Tek projection:** `ListingSearchProjection` (Phase 1'in ilk alt-adımı)

**İçerik:**
1. **Migration:** `listing_search_projection` tablosuna `tenant_id` (nullable, indexed) + `ulke_id` (nullable) + `city_id` (nullable) + `district_id` (nullable) kolonları ekle
2. **Backfill:** `ilanlar.tenant_id`'den join ile backfill (§18.1)
3. **Negatif tenant testi:** Tenant A'nın verisinin Tenant B'ye sızmadığını doğrula

**İçermez:**
- ❌ Diğer 5 projection'a migration
- ❌ BelongsToTenant trait ekleme (model değişikliği)
- ❌ Writer/projector kodu
- ❌ Rebuild komutu
- ❌ rand() kaldırma

### 21.3 Negatif Tenant Testi Senaryosu

```
Test: Tenant izolasyonu — ListingSearchProjection

Hazırlık:
  1. Tenant A (id=1) için 3 ilan kaydı oluştur (listing_search_projection'a tenant_id=1 ile)
  2. Tenant B (id=2) için 2 ilan kaydı oluştur (listing_search_projection'a tenant_id=2 ile)
  3. Tenant context'i Tenant A olarak set et

Test 1 (Pozitif):
  - ListingSearchProjection::count() === 3
  - Tüm kayıtların tenant_id === 1

Test 2 (Negatif — sızma testi):
  - ListingSearchProjection::withoutTenant()->count() === 5 (tüm tenant'lar)
  - Tenant B'nin kayıtları Tenant A'nın sorgusunda GÖRÜNMEMELİ
  - ListingSearchProjection::where('tenant_id', 2)->count() === 0 (Tenant A context'inde)

Test 3 (Cross-tenant erişim):
  - Tenant A context'inde Tenant B'nin listing_id'si ile sorgu → null/not-found
  - ListingSearchProjection::where('listing_id', $tenantB_listing_id)->first() === null

Test 4 (Tenant context yok — console):
  - TenantContextService::hasTenant() === false
  - ListingSearchProjection::count() === 5 (scope uygulanmaz, tüm kayıtlar)
```

### 21.4 Kabul Kriterleri (İlk Kod Paketi)

1. `listing_search_projection` tablosunda `tenant_id` kolonu var (nullable, indexed)
2. Backfill sonrası tüm kayıtların `tenant_id`'si dolu (NULL yok)
3. Tenant A context'inde sadece Tenant A'nın kayıtları görünür
4. Tenant B context'inde sadece Tenant B'nin kayıtları görünür
5. Tenant context yokken tüm kayıtlar görünür (console/job)
6. Negatif tenant testi geçiyor (sızıntı yok)
7. Mevcut reader'lar (`OpportunityEngineService`) çalışmaya devam ediyor (regression yok)

### 21.5 Sıralı Kod Paketleri (Review sonrası)

```
Paket 1: ListingSearchProjection — tenant migration + negatif test (BU PAKET)
  ↓ onay
Paket 2: ListingSearchProjection — BelongsToTenant trait + $fillable güncelle
  ↓ onay
Paket 3: ListingSearchProjection — ListingSearchProjector (event listener) + rebuild
  ↓ onay
Paket 4: MarketTrendProjection — tenant migration + negatif test
  ↓ onay
... (her projection için aynı sıralı yaklaşım)
```

**Kural:** Her paket tek bir değişiklik yapar. 6 tabloyu aynı anda değiştirmek yasaktır.

---

> **Rapor Sonu.** Bu rapor, spec'teki 10 kabul kriterinin tümüne net cevaplar sunmuştur. §18-§21 bölümleri reviewer geri bildirimi üzerine eklenmiştir: tenant_id backfill politikası, MarketTrend tenant kararı, confidence/status sözleşmesi ve sıralı kod paketi yaklaşımı. Kod yazılmamış, migration oluşturulmamış, trait eklenmemiştir. Sadece araştırma, analiz ve mimari tasarım yapılmıştır.
