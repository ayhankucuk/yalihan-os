# CQRS Projection Tenant + Rebuild — Mimari Araştırma Görevi

> **Görev tipi:** Architect (araştırma + tasarım, kod yazma YOK)
> **Atanan:** Kodex
> **Tarih:** 2026-09-06
> **Önceden kabul:** Bu araştırma kabul edilmeden migration, trait, writer veya backfill yazılmaz.

---

## 1. Bağlam

YALIHAN OS'de 6 CQRS read-model projection tablosu tanımlıdır. Bu tablolar AI servisleri tarafından okunur ancak:

- 6 tablonun da **0 kaydı** var
- 6 tablonun hiçbirinde **`tenant_id` kolonu yok**
- 6 modelin hiçbiri **`BelongsToTenant` trait** kullanmıyor
- 4 AI servisi **`rand()` fallback** ile rastgele veri üretiyor
- 2 projection'ın **writer'ı yok** (ListingSearch, MarketTrend)
- 1 projection'ın writer'ı **simulation mode** (ListingVelocity — "In a real system, these would come from Analytics/Logs")
- 2 projection'ın writer'ı **gerçek ama çağrılmıyor** (BuyerIntent, TalepMatch — BuyerIntentExtractionService var ama hiçbir event/job tetiklemiyor)

**Kullanıcı talimatı:** "Önce her projection için: `kaynak veri → writer/event → projection schema → tenant scope → reader → beklenen sonuç` zinciri kurulmalı. Writer yokken tabloya yalnızca kolon ve trait eklemek, boş ve anlamsız projection üretir."

---

## 2. Mevcut Durum — 6 Projection Envanteri

### 2.1 ListingSearchProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `listing_search_projection` |
| Model | `app/Models/Projections/ListingSearchProjection.php` |
| Writer | ❌ YOK — hiçbir projector/listener/job bu tabloya yazmıyor |
| Reader'lar | `OpportunityEngineService`, `OpportunityScoringService`, `OpportunityDetectionService`, `AIListingSearchService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `listing_id, title, city, district, price, room_count, property_type, features, portfolio_health, seo_score` |
| İngilizce alanlar | `title` (BEKCI LOW violation — SAB SEALED ile geçici olarak ignore edildi) |

**Reader kullanımı:**
```php
// OpportunityEngineService.php
$query = ListingSearchProjection::query()
    ->select(['listing_id', 'title', 'price', 'city', 'district', 'property_type', 'portfolio_health', 'seo_score']);
```

### 2.2 ListingVelocityProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `listing_velocity_projections` |
| Model | `app/Models/Projections/ListingVelocityProjection.php` |
| Writer | `ListingVelocityService::syncVelocity()` — **SIMULATION** ("In a real system, these would come from Analytics/Logs") |
| Reader'lar | `DealRadarService`, `PortfolioDoctorService`, `SellerStrategyService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `listing_id, view_count, favorite_count, inquiry_count, share_count, last_activity_at, activity_score` |

**Writer davranışı:**
```php
// ListingVelocityService.php:25
// Simulation: In a real system, these would come from Analytics/Logs
// For this phase, we use existing projection data or initial state
$score = $this->calculateActivityScore($projection);
$projection->update(['activity_score' => $score, 'last_activity_at' => now()]);
```
`view_count`, `favorite_count`, `inquiry_count`, `share_count` hiç doldurulmuyor — her zaman 0.

### 2.3 MarketTrendProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `market_trend_projections` |
| Model | `app/Models/Projections/MarketTrendProjection.php` |
| Writer | ❌ YOK |
| Reader'lar | `DealRadarService`, `DealScoringService`, `MarketHeatService`, `OpportunityEngineService`, `PortfolioDoctorService`, `SellerStrategyService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `city, district, property_type, avg_price, median_price, price_change_7d, price_change_30d, demand_index, listing_count` |

**Reader kullanımı:**
```php
// DealRadarService.php:64
$market = MarketTrendProjection::where('city', $listing->il)->where('district', $listing->ilce)->first();
// PortfolioDoctorService.php:63
$market = MarketTrendProjection::where('city', $listing->il)->where('district', $listing->ilce)->first();
```
`city` ve `district` alanları string olarak sorgulanıyor — `ilanlar.il` (int ID) ile karşılaştırılıyor. **Tip uyumsuzluğu riski.**

### 2.4 BuyerInterestProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `buyer_interest_projections` |
| Model | `app/Models/Projections/BuyerInterestProjection.php` |
| Writer | ❌ YOK |
| Reader'lar | `OpportunityEngineService`, `SellerStrategyService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `listing_id, candidate_count, avg_match_score, top_match_score, high_intent_buyer_count, recent_query_count` |

### 2.5 TalepMatchProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `talep_match_projection` |
| Model | `app/Models/Projections/TalepMatchProjection.php` |
| Writer | `BuyerIntentExtractionService::syncTalepMatch()` — **GERÇEK ama çağrılmıyor** |
| Reader'lar | `BuyerMatchDetectionService`, `PortfolioDoctorService`, `SellerStrategyService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `talep_id, buyer_id, city, district, min_price, max_price, room_count, features, property_type, purchase_intent_level` |

**Writer davranışı:**
```php
// BuyerIntentExtractionService.php:64
TalepMatchProjection::updateOrCreate(
    ['talep_id' => $talep->id],
    ['buyer_id' => $talep->kisi_id, 'city' => $talep->il?->il_adi, ...]
);
```
`syncTalepMatch()` metodu var ama hiçbir event listener, job veya controller tarafından çağrılmıyor.

### 2.6 BuyerIntentProjection

| Boyut | Durum |
|-------|-------|
| Tablo | `buyer_intent_projection` |
| Model | `app/Models/Projections/BuyerIntentProjection.php` |
| Writer | `BuyerIntentExtractionService::syncBuyerIntent()` — **GERÇEK ama çağrılmıyor** |
| Reader'lar | `BuyerMatchDetectionService`, `BuyerMatchScoringService` |
| tenant_id | ❌ YOK |
| BelongsToTenant | ❌ YOK |
| Kayıt sayısı | 0 |
| $fillable | `buyer_id, locale, preferred_city, preferred_district, min_budget, max_budget, property_types, room_preferences, feature_preferences, urgency_level, recent_activity_score, last_contact_at` |

---

## 3. Çalışan Reference Pattern — `ListingProjector`

Sistemde çalışan bir CQRS projector örneği var:

| Boyut | Durum |
|-------|-------|
| Tablo | `proj_listings` |
| Projector | `app/Listeners/ListingProjector.php` |
| Event'ler | `ListingCreated`, `ListingUpdated` |
| Idempotency | `proj_event_offsets` tablosu ile event ID takibi |
| Queue | `projections` kuyruğu, `ShouldQueue` |
| Retry | 3 deneme, backoff [10, 30, 60] |
| Tenant | `proj_listings` tablosunda `tenant_id` var mı? — **ARAŞTIRILACAK** |

```php
// ListingProjector.php — çalışan pattern
DB::transaction(function () use ($event) {
    DB::table('proj_listings')->updateOrInsert(
        ['ilan_id' => $event->listingId],
        ['baslik' => $event->title, 'yayin_durumu' => $event->yayinDurumu, ...]
    );
    $this->markAsProcessed($event->eventId);
});
```

---

## 4. AI Servislerindeki `rand()` Fallback'leri

```php
// DealRadarService.php:96-97
if ($searchFrequency === 0) $searchFrequency = rand(10, 80);
if ($buyerMatchDensity === 0) $buyerMatchDensity = rand(20, 90);

// PortfolioDoctorService.php:67-68
$listingViewVelocity = min(100, $velocity?->view_count ?? rand(10, 80));
$imageQualityScore = rand(40, 95);
```

Bu fallback'ler projection'lar boş olduğu için **her zaman** çalışır. Production'da AI önerileri rastgele sayılara dayanır.

---

## 5. Araştırma Soruları — Her Projection İçin

### 5.1 Kaynak Veri (Source Data)

Her projection için kaynak veri nereden gelir?

| Projection | Olası kaynak |
|-----------|-------------|
| ListingSearch | `ilanlar` tablosu (yayın durumu, fiyat, il, ilce, emlak_tipi) |
| ListingVelocity | `ilan_goruntuleme_log` (var mı?), `favoriler`, `talepler`, `paylasim_log` (var mı?) |
| MarketTrend | `ilanlar` tablosundan agregasyon (il + ilce + emlak_tipi bazlı ortalama/medyan fiyat) |
| BuyerInterest | `talepler` tablosu, `buyer_match_queue` |
| TalepMatch | `talepler` tablosu (mevcut writer var ama çağrılmıyor) |
| BuyerIntent | `kisiler` + `talepler` (mevcut writer var ama çağrılmıyor) |

**Soru:** Her projection için kaynak veri tablosu/event'i kesin olarak belirlenmeli. Mevcut şemada `ilan_goruntuleme_log`, `paylasim_log` gibi tablolar var mı? Yoksa view_count/favorite_count/inquiry_count/share_count için yeni bir event/log tablosu mu tasarlanmalı?

### 5.2 Writer/Event Tasarımı

Her projection için writer nasıl olmalı?

| Projection | Writer tipi | Tetikleyici |
|-----------|------------|------------|
| ListingSearch | Event listener (ListingProjector pattern) | `IlanCreated`, `IlanYayinlandi`, `IlanPriceChanged` |
| ListingVelocity | Event listener + scheduled job | `IlanViewed` (yeni event?), `IlanFavorilendi`, günlük aggregation job |
| MarketTrend | Scheduled aggregation job | Günde 1 kez `ilanlar` tablosundan il+ilce+tip bazlı agregasyon |
| BuyerInterest | Event listener + scheduled job | `TalepReceived`, `BuyerMatchDetected`, günlük aggregation |
| TalepMatch | Event listener | `TalepReceived` (mevcut writer çağrılmalı) |
| BuyerIntent | Event listener | `TalepReceived`, `LeadOlusturuldu` (mevcut writer çağrılmalı) |

**Soru:** ListingVelocity için `view_count` verisi nereden gelecek? Analytics log tablosu mu, Redis counter mı, yoksa mevcut bir tablo mu? Mevcut sistemde ilan görüntülenme takibi var mı?

### 5.3 Projection Schema

Her projection tablosuna `tenant_id` eklenmeli mi? Evet — ama:

**Soru 1:** `tenant_id` kolonu nullable mı olmalı (backfill öncesi), yoksa NOT NULL mu?
**Soru 2:** Backfill stratejisi ne? `ilanlar.tenant_id` → `listing_search_projection.tenant_id` join ile mi?
**Soru 3:** `MarketTrendProjection` için tenant_id nasıl belirlenecek? (Bu tablo il+ilce bazlı agregasyon — birden fazla tenant aynı il+ilce'de ilan paylaşabilir mi?)
**Soru 4:** Unique constraint'ler ne olmalı?
- `listing_search_projection`: `UNIQUE(tenant_id, listing_id)`
- `listing_velocity_projections`: `UNIQUE(tenant_id, listing_id)`
- `market_trend_projections`: `UNIQUE(tenant_id, city, district, property_type)` — veya tenant olmadan `UNIQUE(city, district, property_type)`?
- `buyer_interest_projections`: `UNIQUE(tenant_id, listing_id)`
- `talep_match_projection`: `UNIQUE(tenant_id, talep_id)`
- `buyer_intent_projection`: `UNIQUE(tenant_id, buyer_id)`

### 5.4 Tenant Scope

`BelongsToTenant` trait eklendiğinde:
- Global scope otomatik `WHERE tenant_id = current_tenant` ekler
- Ama projection tabloları `HasCountryScope` kullanıyor — `BelongsToTenant` ile çakışır mı?
- `HasCountryScope` ve `BelongsToTenant` aynı modelde kullanılabilir mi?
- `MarketTrendProjection` tenant bazlı mı, yoksa global mi olmalı? (Pazar trendleri tüm tenant'lar için geçerli olabilir)

**Soru:** `HasCountryScope` (ülke bazlı) ile `BelongsToTenant` (tenant bazlı) aynı modelde nasıl çalışır? Öncelik sırası ne? Country scope tenant'ın içinde mi filtreler, yoksa bağımsız mı?

### 5.5 Reader (Tüketici) Sözleşmesi

Her reader'ın projection'dan ne beklediği:

| Reader | Projection | Okunan alanlar | Filtre | Sıralama |
|--------|-----------|----------------|--------|----------|
| OpportunityEngineService | ListingSearch | listing_id, title, price, city, district, property_type, portfolio_health, seo_score | city, district, property_type, price range | - |
| OpportunityEngineService | BuyerInterest | listing_id, candidate_count, avg_match_score, top_match_score, high_intent_buyer_count, recent_query_count | listing_id | - |
| OpportunityEngineService | MarketTrend | avg_price, median_price, demand_index, listing_count | city, district, property_type | - |
| DealRadarService | ListingVelocity | view_count, activity_score | listing_id | - |
| DealRadarService | MarketTrend | demand_index, listing_count | city, district | - |
| PortfolioDoctorService | ListingVelocity | view_count, activity_score | listing_id | - |
| PortfolioDoctorService | MarketTrend | avg_price, demand_index | city, district | - |
| PortfolioDoctorService | TalepMatch | count() | city | - |
| SellerStrategyService | MarketTrend | avg_price, median_price, demand_index | city, district | - |
| SellerStrategyService | ListingVelocity | view_count | listing_id | - |
| SellerStrategyService | TalepMatch | count() | city + price range | - |
| SellerStrategyService | BuyerInterest | candidate_count, avg_match_score | listing_id | - |
| DealScoringService | MarketTrend | avg_price, median_price, demand_index | city, district | - |
| MarketHeatService | MarketTrend | demand_index, listing_count, price_change_7d, price_change_30d | city, district, property_type | - |
| BuyerMatchDetectionService | TalepMatch | all fields | property_type, city, district, min_price, max_price | - |
| BuyerMatchDetectionService | BuyerIntent | all fields | property_types (JSON), preferred_city, preferred_district, min_budget, max_budget | - |
| BuyerMatchScoringService | BuyerIntent | urgency_level, recent_activity_score | buyer_id | - |
| AIListingSearchService | ListingSearch | all fields | city, district, price, room_count, property_type, features | - |
| OpportunityScoringService | ListingSearch | portfolio_health, seo_score | - | - |
| OpportunityDetectionService | ListingSearch | all | - | - |

**Soru:** Reader'lar `city` ve `district` alanlarını string olarak sorguluyor (örn. `where('city', $ilan->il)`). Ama `ilanlar.il` integer (il ID). Projection'a il ID mi, il adı mı yazılmalı? Reader'lar hangi formata göre çalışıyor?

### 5.6 Beklenen Sonuç (Expected Result)

Her projection doldurulduğunda AI servislerinin ne yapması bekleniyor?

| Projection | Dolu olduğunda | AI servis etkisi |
|-----------|---------------|-----------------|
| ListingSearch | OpportunityEngine gerçek ilanları skorlayabilir | `rand()` fallback kalkar, gerçek portfolio_health/seo_score kullanılır |
| ListingVelocity | DealRadar gerçek view/activity verisi kullanır | `rand(10, 80)` kalkar |
| MarketTrend | DealScoring gerçek pazar trendi kullanır | `rand(20, 90)` kalkar |
| BuyerInterest | OpportunityEngine gerçek alıcı sinyali kullanır | Boş sorgu → 0 yerine gerçek candidate_count |
| TalepMatch | BuyerMatchDetection gerçek talep eşleşmesi yapar | Boş sonuç yerine gerçek adaylar |
| BuyerIntent | BuyerMatchScoring gerçek urgency_level kullanır | 0 yerine gerçek urgency |

---

## 6. İstenen Çıktı

Kodex'ten beklenen mimari araştırma dokümanı:

### 6.1 Her Projection İçin Tam Zincir

```
kaynak veri → writer/event → projection schema → tenant scope → reader → beklenen sonuç
```

6 projection'ın her biri için bu zincir tam olarak tanımlanmalı.

### 6.2 Tenant İzolasyon Stratejisi

- `tenant_id` kolonu ekleme planı (nullable → backfill → NOT NULL → index)
- `BelongsToTenant` + `HasCountryScope` birlikteliği
- `MarketTrendProjection` için tenant vs. global karar
- Unique constraint'ler

### 6.3 Writer Tasarımı

- Event-driven vs. scheduled job vs. hybrid
- Her projection için tetikleyici event/job
- Idempotency stratejisi (ListingProjector pattern: `proj_event_offsets`)
- `BuyerIntentExtractionService`'in çağrılma zinciri (mevcut writer'ı aktive etme)

### 6.4 Rebuild Stratejisi

- Deterministic rebuild: `php artisan projection:rebuild {name}`
- Backfill: mevcut `ilanlar`, `talepler`, `kisiler` verisinden projection doldurma
- Sıralama: hangi projection önce doldurulmalı (bağımlılık: MarketTrend ← ListingSearch)

### 6.5 `rand()` Fallback Kaldırma Planı

- Her `rand()` çağrısı için: projection dolu olduğunda ne ile değiştirilecek?
- Fallback değer: 0 mı, null mı, yoksa "veri yok" durumu mu?

### 6.6 Risk ve Bağımlılık Analizi

- `city`/`district` alanları: string (il adı) vs. int (il ID) uyumsuzluğu
- `MarketTrendProjection` çok-tenant paylaşımı
- `ListingVelocity` için view_count veri kaynağı (analytics log yok)
- Migration sırası: tenant_id kolon → backfill → index → trait → writer → rebuild

---

## 7. Kısıtlar

- ❌ Bu görevde kod yazılmaz
- ❌ Migration oluşturulmaz
- ❌ Trait eklenmez
- ✅ Sadece araştırma, analiz ve mimari tasarım
- ✅ Mevcut kod okunur, reader/writer bağımlılıkları çıkarılır
- ✅ Her projection için tam zincir tanımlanır
- ✅ Risk ve bağımlılık haritası çıkarılır
- ✅ Önerilen uygulama sırası (phase'ler halinde) sunulur

---

## 8. Kabul Kriterleri

Araştırma dokümanı şu sorulara net cevap vermeli:

1. Her projection için `kaynak veri → writer → schema → tenant → reader → sonuç` zinciri tam tanımlanmış mı?
2. `tenant_id` kolonu hangi tablolara, nasıl (nullable mı NOT NULL mı) eklenecek?
3. `BelongsToTenant` ve `HasCountryScope` aynı modelde çalışır mı?
4. `MarketTrendProjection` tenant bazlı mı, global mi?
5. `city`/`district` alanları string mi, int mi olmalı? Reader'lar hangisini bekliyor?
6. `ListingVelocity` için `view_count` verisi nereden gelecek? Yeni event mi, mevcut tablo mu?
7. `BuyerIntentExtractionService` nasıl aktive edilecek? Event listener mı, job mı?
8. Rebuild sırası ne? Hangi projection önce?
9. `rand()` fallback'ler ne ile değiştirilecek?
10. Idempotency stratejisi ne? `proj_event_offsets` pattern mi, `updateOrCreate` mi?

---

## 9. Referans Dosyalar

| Dosya | İçerik |
|-------|--------|
| `docs/architecture/capability-research-2026-09-06.md` | ARAŞTIRMA-2: CQRS Projection Doluluk Durumu |
| `app/Listeners/ListingProjector.php` | Çalışan projector pattern (proj_listings) |
| `app/Listeners/LeadProjector.php` | Çalışan projector pattern (proj_leads) |
| `app/Services/AIDeal/ListingVelocityService.php` | Simulation writer |
| `app/Services/AIMatch/BuyerIntentExtractionService.php` | Gerçek ama çağrılmayan writer |
| `app/Services/AI/OpportunityEngineService.php` | Reader — 3 projection okur |
| `app/Services/AI/DealRadarService.php` | Reader — 2 projection + rand() |
| `app/Services/AI/PortfolioDoctorService.php` | Reader — 3 projection + rand() |
| `app/Services/AI/SellerStrategyService.php` | Reader — 4 projection |
| `app/Services/AIMatch/BuyerMatchDetectionService.php` | Reader — 2 projection |
| `app/Models/Projections/*.php` | 6 projection modeli |
| `app/Traits/EnforcesContext7Guard.php` | Context7 guard trait |
| `app/Traits/BelongsToTenant.php` | Tenant izolasyon trait (araştırılacak) |
