# Sprint 6.2 — Location Intelligence
**Charter** | Yalıhan Emlak AI OS · ERA III

> **Stratejik Öncelik:** Business Value > Product Value > Engineering Quality
> **Model:** Claude Opus 4.8 (mimari) → Claude Sonnet 4.6 (implementasyon)

---

## 1. Vision

**Tek cümle:** Bir adres girildiğinde, sistem otomatik olarak koordinatlarını bulur, çevresini analiz eder, Location Score üretir ve bunu AI süreçlerinin tamamında kullanır.

**Bu Sprint ne kazandırır:**
- Konumu anlayan, analiz eden ve değerlendiren Property Intelligence Platform
- Sonraki Media, Publishing ve AI Copilot sprintleri için temel altyapı

**İleride neye kapı açar:**
- Airbnb açıklaması (konum bölümü otomatik)
- SEO meta description
- Fiyat önerisi (konum bazlı)
- Benzer ilan analizi

---

## 2. Mevcut Durum Analizi

### 2.1 Hazır Olanlar ✅

| Katman | Dosya/Servis | Durum |
|--------|-------------|-------|
| POI Data | `PoiService.php` | ✅ `findNearby()` mevcut |
| POI Analysis | `LocationIntelligenceService.php` | ✅ `analyze()` mevcut — 3 alt skor |
| POI Config | `config/location_intelligence.php` | ✅ POI groups, buckets, weights |
| Spatial Scout | `SpatialScoutService.php` | ✅ `hesapla()` mevcut |
| Admin Location | `AdresLocationService.php` | ✅ DB boundary queries |
| Location Copilot | `LocationCopilotService.php` | ✅ Listing analizi, coordinate quality |
| Location DTO | `LocationInsightDTO.php` | ✅ DTO yapısı mevcut |

### 2.2 Eksik Olanlar 🔴

| Katman | Durum | Not |
|--------|-------|-----|
| **Geocoding Service** | 🔴 Yok | Adres → Koordinat dönüşümü yok |
| **API Routes** | 🔴 Yok | `/api/location/analyze` route yok |
| **Ilan ile Entegrasyon** | 🔴 Ayrık | `LocationIntelligenceService` bir Ilan'a bağlı değil |
| **Pipeline Orchestrator** | 🔴 Yok | Tüm adımları zincirleyen tek servis yok |
| **UI Widget** | 🔴 Yok | Dashboard'da Location Card yok |
| **AI Location Summary** | ⚠️ Kısmen | `LocationCopilotService` var ama pipeline'a entegre değil |

### 2.3 Mimari Sorun

```
Mevcut:
  Ilan → [BOŞLUK] → LocationIntelligenceService.analyze(lat, lng)
                    ↑
              Bu servis DB'den Ilan'a bakmıyor, parametre alıyor.

Hedef:
  Ilan → LocationOrchestrator
           ├─ Geocoding (adres → lat/lng)
           ├─ POI Analysis (lat/lng → score)
           ├─ AI Summary (score + POI → Türkçe özet)
           └─ Save to Ilan (location_data JSON)
```

---

## 3. Teslim Edilecekler (Definition of Done)

### 3.1 Core Capability

- [ ] **GeocodingService** — Türkçe adres → `(lat, lng)` dönüşümü
  - Nominatim (OpenStreetMap) entegrasyonu — ücretsiz, API key gerektirmez
  - Fallback: Türkiye Adres DTO sistemi (il/ilçe/mahalle → orta nokta koordinatı)
  - Cache: 30 gün TTL

- [ ] **LocationOrchestrator** — Tek giriş noktası
  ```php
  $result = $orchestrator->analyze(ilanId: 123);
  // Çıktı: { coordinates, poi_data, location_score, ai_summary, status }
  ```

- [ ] **IlanLocationSyncJob** — Arka plan işi
  - İlan koordinatı değiştiğinde tetiklenir
  - Location Score + POI data'yı `ilanlar.location_data` JSON kolonuna kaydeder

- [ ] **LocationController (API)** — Http katmanı
  - `POST /api/location/analyze` — `{ ilan_id }` → full location report
  - `GET /api/location/score/{ilan_id}` → sadece score + confidence

### 3.2 Database Migration

- [ ] `ilanlar.location_data` JSON kolonu (nullable)
- [ ] `ilanlar.location_score` integer (nullable) — cache için
- [ ] `ilanlar.location_score_confidence` enum ['HIGH','MEDIUM','LOW','VERY_LOW'] (nullable)

### 3.3 Dashboard Widget

- [ ] **LocationIntelligenceCard** — Cockpit dashboard için
  - Score ring (0–100)
  - 3 alt skor bar
  - En yakın 3 POI grubu
  - AI özet metni
  - "Yeniden Analiz Et" butonu

### 3.4 Sprint 6.1 Closure (Parallel)

- [ ] `v6.1-workspace-runtime-certified` Git tag
- [ ] `Sprint-6.1-CLOSURE/` klasörü — rapor + mimari diagram + KPI snapshot

---

## 4. Kapsam Dışı (Explicitly Out of Scope)

- Walk Score / Transit Score API entegrasyonu (Sprint 6.3+)
- Beach/Marina özel POI tipleri (veri yok)
- Ulaşım süreleri (GTFS/Routing API — ayrı sprint)
- Airbnb açıklaması üretimi (Publishing Engine sprinti)
- Çoklu dil çeviri

---

## 5. Pipeline Akışı

```
┌─────────────────────────────────────────────────────────────────────┐
│  INPUT: Ilan ID                                                      │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 1: Adres Topla                                                 │
│  IlanService → getLocationText(ilan)                                │
│  Çıktı: "Gölbet Mahallesi, Yalıkavak, Bodrum, Muğla, Türkiye"      │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 2: Geocoding                                                   │
│  GeocodingService.resolve(address)                                  │
│  ├─ Nominatim (OSM) → lat/lng                                        │
│  └─ Fallback: TürkiyeAdresDB → il/ilçe merkez koordinatı            │
│  Çıktı: { lat: 37.063, lng: 27.429, source: 'nominatim' }          │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 3: POI Analysis                                                │
│  LocationIntelligenceService.analyze(lat, lng)                      │
│  Çıktı: LocationInsightDTO                                          │
│    - location_signal_score (0–100)                                  │
│    - poi_access_score, poi_density_score, poi_coverage_score        │
│    - top_nearby_groups[]                                            │
│    - confidence_score, confidence_label                             │
│    - demand_modifier                                               │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 4: AI Location Summary (isteğe bağlı)                         │
│  YalihanCortex → location_summary_prompt                             │
│  "Bu konum {score} puan almıştır. {topGroups}..."                 │
│  Çıktı: "Bodrum Yalıkavak'ta denize 400m mesafede, marina..."       │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 5: Persist                                                     │
│  IlanCrudService.updateLocationData(ilanId, $result)                │
│  → ilanlar.location_data = JSON                                     │
│  → ilanlar.location_score = int                                     │
│  → ilanlar.location_score_confidence = enum                         │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│  OUTPUT                                                              │
│  { status, score, confidence, sub_scores, top_groups,               │
│    ai_summary, coordinates, source }                                │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. Dosya Yapısı

```
app/
├── Services/
│   ├── Location/
│   │   ├── GeocodingService.php          🆕
│   │   ├── LocationOrchestrator.php      🆕
│   │   ├── PoiService.php                ✅ (mevcut)
│   │   └── SpatialScoutService.php       ✅ (mevcut)
│   └── Ilan/
│       └── IlanLocationSyncService.php   🆕
├── Http/
│   └── Controllers/Api/
│       └── LocationController.php        🆕
├── DTOs/Location/
│   ├── LocationAnalysisResultDTO.php     🆕
│   └── LocationInsightDTO.php            ✅ (mevcut)
├── Jobs/
│   └── SyncIlanLocationJob.php           🆕
database/migrations/
├── xxxx_add_location_data_to_ilanlar.php  🆕
├── xxxx_add_location_score_to_ilanlar.php 🆕
config/
└── location_intelligence.php              ✅ (mevcut, güncelleme yok)
resources/views/components/
└── location-intelligence-card.blade.php   🆕
routes/
└── api.php                                🆕 route tanımları
Sprint-6.2-LOCATION-INTELLIGENCE/
├── CHARTER.md                             📋 (bu dosya)
├── CLOSURE/
│   ├── certification-report.md
│   └── architecture-diagram.md
└── PROGRESS.md
```

---

## 7. Geocoding Stratejisi

### 7.1 Birincil: Nominatim (OSM)

```php
// Ücretsiz, rate limit: 1 req/s
GET https://nominatim.openstreetmap.org/search
  ?q={address}
  &format=json
  &addressdetails=1
  &limit=1
  &countrycodes=tr
```

**Avantaj:** Ücretsiz, açık, sürekli güncellenen Türkiye verisi
**Dezavantaj:** Rate limit (1 req/s), uptime garantisi yok

### 7.2 Fallback: TürkiyeAdresDB Koordinatları

Mevcut `mahalleler` tablosunda `lat/lng` zaten var:
- `lat` ve `lng` kolonları mevcut
- Her mahalle için orta nokta koordinatı kayıtlı

**Fallback mantığı:**
1. Nominatim başarısız veya timeout
2. Ilan'ın il_id + ilce_id + mahalle_id'sini al
3. `mahalleler` tablosundan koordinatı al
4. Daha az hassas ama çalışır

### 7.3 Cache

- Nominatim sonuçları: 30 gün Redis/DB cache
- Key: `geocode:{normalized_address}`
- Adres normalize edilir (lowercase, trim, Türkçe karakterler)

---

## 8. DTO Yapısı

### 8.1 LocationAnalysisResultDTO (Pipeline çıktısı)

```php
readonly class LocationAnalysisResultDTO
{
    public function __construct(
        public readonly string $status,            // 'ok' | 'no_coordinates' | 'insufficient_data' | 'error'
        public readonly ?int $score,               // null if status != 'ok'
        public readonly string $confidence,         // 'HIGH' | 'MEDIUM' | 'LOW' | 'VERY_LOW'
        public readonly int $poi_access_score,
        public readonly int $poi_density_score,
        public readonly int $poi_coverage_score,
        public readonly array $top_groups,         // [{group, label, closest_m, count}]
        public readonly ?float $lat,
        public readonly ?float $lng,
        public readonly string $geocode_source,     // 'nominatim' | 'adres_db' | 'manual' | 'none'
        public readonly ?string $ai_summary,
        public readonly array $reason_codes,
    ) {}
}
```

---

## 9. API Sözleşmesi

### `POST /api/location/analyze`

**Request:**
```json
{
  "ilan_id": 123
}
```

**Response (200 OK):**
```json
{
  "status": "ok",
  "data": {
    "score": 72,
    "confidence": "HIGH",
    "sub_scores": {
      "poi_access_score": 28,
      "poi_density_score": 22,
      "poi_coverage_score": 22
    },
    "top_groups": [
      { "group": "beach", "label": "Plaj", "closest_m": 320, "count": 3 },
      { "group": "food_social", "label": "Yeme-İçme", "closest_m": 450, "count": 12 },
      { "group": "shopping", "label": "Alışveriş", "closest_m": 680, "count": 5 }
    ],
    "coordinates": { "lat": 37.063, "lng": 27.429 },
    "geocode_source": "nominatim",
    "ai_summary": "Bodrum Yalıkavak'ta denize sadece 320m mesafede, yat limanına 450m uzaklıkta.",
    "reason_codes": ["near_beach_access", "strong_food_social_access", "moderate_poi_coverage"],
    "demand_modifier": 4
  }
}
```

**Response (422 — Yetersiz veri):**
```json
{
  "status": "insufficient_data",
  "message": "Bu bölgede yeterli POI verisi bulunamadı.",
  "data": { "score": null, "confidence": "VERY_LOW" }
}
```

### `GET /api/location/score/{ilan_id}`

**Response (200 OK):**
```json
{
  "ilan_id": 123,
  "score": 72,
  "confidence": "HIGH",
  "demand_modifier": 4,
  "last_analyzed_at": "2026-07-08T10:00:00Z"
}
```

---

## 10. Başarı Kriterleri

| Kriter | Hedef | Ölçüm |
|--------|-------|-------|
| Geocoding success rate | ≥ 95% | Nominatim + fallback |
| Pipeline completion | ≤ 3 saniye | /api/location/analyze response time |
| Location score coverage | İlanların ≥ 80%'i scored | `ilanlar.location_score IS NOT NULL` |
| Dashboard widget | Render süresi ≤ 200ms | Chrome DevTools |
| Geocoding cache hit | ≥ 70% | Cache hit log |
| Test coverage | ≥ 80% | PHPUnit coverage |

---

## 11. Riskler

| Risk | Olasılık | Etki | Mitigation |
|------|----------|------|------------|
| Nominatim rate limit | Orta | Yüksek | Redis cache + AdresDB fallback |
| POI verisi yetersiz (kırsal) | Yüksek | Orta | Confidence label + graceful degradation |
| Nominatim down | Düşük | Yüksek | AdresDB zaten fallback olarak mevcut |
| Çok fazla Ilan analizi (queue patlaması) | Orta | Orta | Rate limit + throttle |

---

## 12. Sprint 6.1 Closure Checklist

- [ ] Git tag: `v6.1-workspace-runtime-certified`
- [ ] `Sprint-6.1-CLOSURE/certification-report.md`
- [ ] `Sprint-6.1-CLOSURE/architecture-diagram.md`
- [ ] `Sprint-6.1-CLOSURE/kpi-snapshot.md`
- [ ] `docs/BEKCI_CHANGELOG.md` güncelle
- [ ] `memory/SESSION_NOTES.md` güncelle
- [ ] Claude Code oturum kapatma prosedürü

---

## 13. Başlangıç Todo Listesi

```bash
# 1. Mevcut altyapı doğrula
php artisan bekci:health --detailed

# 2. GeocodingService oluştur
# 3. LocationOrchestrator oluştur
# 4. Migration yaz
# 5. API route + controller
# 6. IlanLocationSyncService + Job
# 7. Dashboard widget
# 8. Test yaz
# 9. Sprint 6.1 kapat
# 10. Full gate + integrity scan
```
