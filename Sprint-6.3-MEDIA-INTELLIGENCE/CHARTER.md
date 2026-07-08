# Sprint 6.3 — Media Intelligence Charter
**Charter** | Yalıhan Emlak AI OS · ERA III

> **Strategic Priority:** Business Value > Product Value > Engineering Quality
> **Model:** Claude Sonnet 4.6 (implementation)

---

## 1. Vision

**Tek cümle:** Fotoğraf yüklendiğinde, sistem otomatik olarak oda türlerini tespit eder, kalite puanını hesaplar, eksik odaları belirler ve en iyi kapak fotoğrafını seçer.

**Business Goal:** Danışmanın manuel fotoğraf incelemesini ortadan kaldırmak.

---

## 2. Mevcut Durum Analizi

### 2.1 Hazır Olanlar ✅

| Katman | Dosya/Servis | Durum |
|--------|-------------|-------|
| Fotoğraf Modeli | `IlanFotografi.php` | ✅ Mevcut, `dosya_yolu`, `kapak_fotografi`, `display_order` |
| Fotoğraf Upload | `IlanPhotoService` | ✅ Upload + S3/Storage mevcut |
| Event Altyapısı | `app/Events/` | ✅ Event sınıfları yapısı mevcut |
| Queue System | Laravel Queue | ✅ `ShouldQueue` + retry/backoff mevcut |
| Workspace | `WorkspaceSummaryService` | ✅ Sprint 6.1 — genişletilecek |

### 2.2 Eksik Olanlar 🔴

| Katman | Durum |
|--------|-------|
| **MediaIntelligenceEngine** | 🔴 Yok |
| **Room Detection** | 🔴 Yok |
| **Image Quality Engine** | 🔴 Yok |
| **Coverage Analyzer** | 🔴 Yok |
| **Hero Image Selection** | 🔴 Yok |
| **Media DTOs** | 🔴 Yok |
| **Media Events** | 🔴 Yok |
| **AnalyzeMediaJob** | 🔴 Yok |
| **Workspace Extension** | 🔴 Yok |
| **Dashboard Card** | 🔴 Yok |

---

## 3. Room Detection — Oda Tipleri

```php
enum OdaTuru: string
{
    case LivingRoom  = 'living_room';
    case Kitchen     = 'kitchen';
    case Bedroom     = 'bedroom';
    case Bathroom    = 'bathroom';
    case Pool        = 'pool';
    case Garden      = 'garden';
    case Terrace     = 'terrace';
    case Exterior    = 'exterior';
    case View        = 'view';
    case Other       = 'other';
}
```

---

## 4. Pipeline Akışı

```
Fotoğraf Yükleme
      ↓
IlanPhotoService.uploadPhotos()
      ↓
Fotoğraf DB'ye kaydedilir
      ↓
AnalyzeMediaJob (queue)
      ↓
┌─────────────────────────────────────────────────────────────┐
│  MediaIntelligenceEngine.analyze(ilanId)                     │
├─────────────────────────────────────────────────────────────┤
│  1. Room Detection Service                                  │
│     → Her fotoğraf → Oda türü + güven skoru                │
│                                                             │
│  2. Image Quality Engine                                   │
│     → Blur, brightness, exposure, sharpness, resolution     │
│     → Media Quality Score (0–100)                          │
│                                                             │
│  3. Coverage Analyzer                                       │
│     → Gerekli odalar vs. tespit edilen odalar             │
│     → Eksik odalar listesi                                 │
│                                                             │
│  4. Hero Image Selection                                   │
│     → Hero Score = f(composition, brightness, sharpness,     │
│                      confidence, room_priority)              │
│     → En yüksek skorlu fotoğraf → kapak fotoğrafı         │
│                                                             │
│  5. Media Health Score                                    │
│     → completeness + quality + coverage                    │
│                                                             │
│  6. Persist → ilan_fotograflari.media_data JSON           │
│              → ilanlar.media_health_score INT               │
│              → Events: MediaAnalyzed, HeroImageSelected     │
└─────────────────────────────────────────────────────────────┘
      ↓
WorkspaceSummaryService (media payload)
      ↓
Dashboard Cockpit Card
```

---

## 5. Dosya Yapısı

```
app/
├── DTOs/Media/
│   ├── MediaRoomDTO.php              🆕
│   ├── MediaPhotoDTO.php             🆕
│   ├── MediaAnalysisDTO.php         🆕
│   └── MediaSummaryDTO.php           🆕
├── Events/Media/
│   ├── MediaAnalyzed.php             🆕
│   ├── HeroImageSelected.php         🆕
│   └── MediaHealthUpdated.php        🆕
├── Jobs/
│   └── AnalyzeMediaJob.php           🆕
├── Services/Media/
│   ├── MediaIntelligenceEngine.php   🆕
│   ├── RoomDetectionService.php      🆕
│   ├── ImageQualityEngine.php       🆕
│   ├── CoverageAnalyzer.php          🆕
│   └── HeroImageSelector.php        🆕
database/migrations/
├── xxxx_add_media_data_to_ilan_fotograflari.php  🆕
├── xxxx_add_media_health_to_ilanlar.php          🆕
└── xxxx_add_room_detection_to_ilan_fotograflari.php 🆕
resources/views/components/
└── media-intelligence-card.blade.php    🆕
tests/
├── Unit/Services/Media/
│   ├── RoomDetectionServiceTest.php   🆕
│   ├── ImageQualityEngineTest.php     🆕
│   ├── CoverageAnalyzerTest.php       🆕
│   ├── HeroImageSelectorTest.php      🆕
│   └── MediaIntelligenceEngineTest.php 🆕
└── Feature/MediaIntelligence/
    ├── MediaIntelligenceApiTest.php    🆕
    └── WorkspaceIntegrationTest.php   🆕
Sprint-6.3-MEDIA-INTELLIGENCE/
├── CHARTER.md                         📋 (bu dosya)
└── CLOSURE/
    └── certification-report.md
```

---

## 6. Database Migration

```sql
-- ilan_fotograflari tablosu
ALTER TABLE ilan_fotograflari
  ADD COLUMN oda_turu      VARCHAR(30)         NULL,
  ADD COLUMN oda_turu_guven float              NULL,
  ADD COLUMN kalite_puani  TINYINT UNSIGNED    NULL,
  ADD COLUMN kalite_ayrinti JSON                NULL,
  ADD COLUMN media_data    JSON                NULL;

-- ilanlar tablosu
ALTER TABLE ilanlar
  ADD COLUMN media_health_score    TINYINT UNSIGNED NULL,
  ADD COLUMN media_quality_score   TINYINT UNSIGNED NULL,
  ADD COLUMN media_tamamlanma_oran TINYINT UNSIGNED NULL,
  ADD COLUMN eksik_odalar         JSON             NULL,
  ADD COLUMN hero_fotograf_id    INT UNSIGNED     NULL;
```

---

## 7. DTO Yapıları

### 7.1 MediaRoomDTO

```php
readonly class MediaRoomDTO
{
    public function __construct(
        public readonly string $oda_turu,     // living_room, kitchen, vb.
        public readonly string $label,        // "Salon", "Mutfak", vb.
        public readonly int $guven_skoru,    // 0–100
        public readonly int $fotoğraf_sayisi,
        public readonly array $fotograf_ids,
    ) {}
}
```

### 7.2 MediaPhotoDTO

```php
readonly class MediaPhotoDTO
{
    public function __construct(
        public readonly int $fotograf_id,
        public readonly ?string $oda_turu,
        public readonly int $oda_guven_skoru,
        public readonly int $kalite_puani,
        public readonly int $hero_skoru,
        public readonly array $kalite_ayrinti,
    ) {}
}
```

### 7.3 MediaAnalysisDTO

```php
readonly class MediaAnalysisDTO
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly int $toplam_fotograf,
        public readonly int $media_health_score,   // 0–100
        public readonly int $media_quality_score,  // 0–100
        public readonly float $tamamlanma_oran,    // 0.0–1.0
        public readonly array $oda_detaylari,     // MediaRoomDTO[]
        public readonly array $eksik_odalar,       // string[]
        public readonly ?int $hero_fotograf_id,
        public readonly array $tum_fotograflar,   // MediaPhotoDTO[]
    ) {}
}
```

### 7.4 MediaSummaryDTO (Workspace payload)

```php
readonly class MediaSummaryDTO
{
    public function __construct(
        public readonly string $health,          // EXCELLENT | GOOD | FAIR | POOR | MISSING
        public readonly int $health_score,
        public readonly int $quality_score,
        public readonly float $coverage,
        public readonly ?string $hero_image_url,
        public readonly array $detected_rooms,
        public readonly array $missing_rooms,
        public readonly int $total_photos,
    ) {}
}
```

---

## 8. Room Detection Stratejisi

### 8.1 Kural Tabanlı (Deterministic) — V1

Yüzeysel ama hızlı:

| Oda Türü | Göstergeler |
|-----------|-------------|
| Pool | `havuz`, `pool`, `yüzme` keyword veya renk analizi (mavi tonlar) |
| Garden | `bahçe`, `garden`, `yeşil alan`, renk histogramı |
| Bathroom | `banyo`, `bathroom`, `wc`, `tuvalet` |
| Kitchen | `mutfak`, `kitchen`, `beyaz eşya` renk dağılımı |
| Bedroom | `yatak odası`, `bedroom`, `yatak`, `beb` |
| Living Room | `salon`, `oturma`, `living`, `tv` |
| Terrace | `terras`, `terrace`, `balkon` |
| Exterior | `dış cephe`, `building`, `facade` |
| View | `manzara`, `sea`, `deniz`, `panorama` |

**AI Vision** ile tam sınıflandırma ayrı bir sprint'e bırakılır (DeepSeek/GPT-4 Vision API entegrasyonu).

### 8.2 Quality Engine — Kural Tabanlı (V1)

```php
ImageQualityEngine::analyze(Storage::path($dosya_yolu)): array
{
    // Blur: Laplacian variance (keskinlik ölçümü)
    // Brightness: Histogram mean (parlaklık)
    // Exposure: Histogram Dağılımı (over/under exposure)
    // Resolution: getimagesize()
    // Perspective: En/Boy oranı kontrolü

    return [
        'blur_score'    => 0–100,
        'brightness'   => 0–100,
        'exposure'     => 0–100,   // 100 = perfectly exposed
        'sharpness'    => 0–100,
        'resolution'   => 0–100,
        'quality_score' => 0–100,  // weighted average
    ];
}
```

### 8.3 Hero Score Formula

```php
hero_score = (
    room_priority[oda_turu] * 0.30 +
    sharpness * 0.25 +
    brightness * 0.20 +
    exposure * 0.15 +
    oda_guven_skoru * 0.10
)
```

Room priority: Pool > View > LivingRoom > Bedroom > Kitchen > Bathroom > Terrace > Garden > Exterior > Other

### 8.4 Media Health Formula

```php
media_health = (
    quality_score * 0.30 +
    coverage_score * 0.40 +
    completeness_bonus * 0.30
)

coverage_score = tespit_edilen_oda_sayisi / gerekli_oda_sayisi * 100
completeness_bonus = min(100, (toplam_fotoğraf / hedef_fotoğraf) * 100)
```

---

## 9. Events

### 9.1 MediaAnalyzed

```php
class MediaAnalyzed
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly int $media_health_score,
        public readonly int $toplam_fotograf,
        public readonly int $hero_fotograf_id,
        public readonly array $eksik_odalar,
    ) {}
}
```

### 9.2 HeroImageSelected

```php
class HeroImageSelected
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly int $hero_fotograf_id,
        public readonly int $hero_skoru,
    ) {}
}
```

### 9.3 MediaHealthUpdated

```php
class MediaHealthUpdated
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly int $old_score,
        public readonly int $new_score,
    ) {}
}
```

Replay-safe: Her event `$this->event` sonrası kaydedilir.

---

## 10. Queue İşleri

### 10.1 AnalyzeMediaJob

```php
class AnalyzeMediaJob implements ShouldQueue
{
    public int $tries = 2;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(
        public readonly int $ilanId,
        public readonly ?string $jobId = null, // idempotency key
    ) {}

    public function uniqueId(): string
    {
        return "media_analysis_{$this->ilanId}";
    }
}
```

---

## 11. API Sözleşmesi

### `POST /api/media/analyze`

**Request:**
```json
{ "ilan_id": 123 }
```

**Response:**
```json
{
  "status": "ok",
  "data": {
    "ilan_id": 123,
    "media_health_score": 78,
    "health": "GOOD",
    "quality_score": 82,
    "coverage": 0.75,
    "total_photos": 15,
    "hero_fotograf_id": 456,
    "detected_rooms": [
      { "oda_turu": "living_room", "label": "Salon", "count": 3 },
      { "oda_turu": "bedroom", "label": "Yatak Odası", "count": 2 },
      { "oda_turu": "pool", "label": "Havuz", "count": 1 }
    ],
    "missing_rooms": ["bathroom", "kitchen", "garden"],
    "photos": [
      {
        "id": 100,
        "oda_turu": "living_room",
        "oda_guven_skoru": 88,
        "kalite_puani": 91,
        "hero_skoru": 82,
        "kalite_ayrinti": { "blur_score": 95, "brightness": 88, "exposure": 90 }
      }
    ]
  }
}
```

---

## 12. Kapsam Dışı (Out of Scope)

- AI description generation
- Translation
- Publishing (Airbnb, Sahibinden, vb.)
- Reservation
- Pricing
- OCR (metin tanıma)
- Video analysis
- AI Vision API entegrasyonu (GPT-4 Vision / DeepSeek Vision) — sonraki sprint

---

## 13. Başarı Kriterleri

| Kriter | Hedef | Ölçüm |
|--------|-------|-------|
| Room Detection accuracy | ≥ 70% (kural tabanlı V1) | Test suite |
| Quality Score calculation | 0–100 aralığında deterministik | Unit test |
| Coverage Analyzer | Eksik oda tespiti doğru | Unit test |
| Hero Selection | En yüksek skorlu fotoğraf seçilsin | Unit test |
| Queue execution | Job başarıyla çalışsın | Feature test |
| Workspace integration | media payload mevcut API'yi bozmasın | Regression test |
| Dashboard card | Cockpit'te görüntülensin | Manual |
| Unit test coverage | ≥ 80% yeni kod | PHPUnit --coverage |
| Feature tests | Tüm endpoint'ler test edilsin | PHPUnit |
| Tenant isolation | Tenant A fotoğrafları Tenant B'ye görünmesin | Feature test |
| Replay safety | Event replay sonrası tutarlı sonuç | Unit test |

---

## 14. Başlangıç Todo

```bash
# 1. Migration oluştur (3 migration)
# 2. DTOs oluştur (4 DTO)
# 3. RoomDetectionService (kural tabanlı V1)
# 4. ImageQualityEngine (blur, brightness, exposure)
# 5. CoverageAnalyzer
# 6. HeroImageSelector
# 7. MediaIntelligenceEngine (orchestrator)
# 8. Events (3 event)
# 9. AnalyzeMediaJob
# 10. WorkspaceSummaryService extension
# 11. API Controller
# 12. Dashboard Card
# 13. Unit testler
# 14. Feature testler
# 15. Closure raporu
```
