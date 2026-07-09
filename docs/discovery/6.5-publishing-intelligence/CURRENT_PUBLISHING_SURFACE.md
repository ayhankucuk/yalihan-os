# Sprint 6.5 Discovery — CURRENT PUBLISHING SURFACE

> **Tarih:** 2026-07-09
> **Sprint:** 6.5 — Publishing Intelligence
> **Tip:** Discovery — Mevcut Durum Analizi

---

## 1. Mevcut Publishing Durumu

Yalıhan OS'de "publishing" kavramı üç farklı katmanda ele alınıyor:

| Katman | Durum | Açıklama |
|--------|-------|-----------|
| **Lifecycle State** | ✅ Var | `WorkspaceState::READY_FOR_PUBLISH` enum değeri mevcut |
| **Karar Agenti** | ✅ Var | `PublishDecisionAgent` workspace lifecycle'ı `READY_FOR_PUBLISH`'a ilerletir |
| **Portal ID Depolama** | ✅ Var | `sahibinden_id`, `hepsiemlak_id`, vb. `Ilan` modelinde `fillable` |
| **Gerçek API Entegrasyonu** | ❌ Yok | Hiçbir portal için HTTP adapter yok |
| **Publish Payload Üretimi** | ❌ Yok | Kanal bazlı içerik üreten servis yok |
| **Publish Package DTO** | ❌ Yok | Hiçbir typed publishing contract yok |

---

## 2. Mevcut Yayınla Karar Akışı

```
Ilan Oluşturulur
       ↓
Workspace Created
       ↓
PhotoAgent tamamlanır → MEDIA_READY
       ↓
DescriptionAgent tamamlanır → DESCRIPTION_READY
       ↓
PropertyScoreAgent tamamlanır → QUALITY_CHECKED
       ↓
PublishDecisionAgent → READY_FOR_PUBLISH
       ↓
[BURADA KOD BİTER — Gerçek yayınlama YOK]
```

**PublishDecisionAgent Çıktısı:**
```php
[
    'decision'       => 'approved',      // approved | needs_review | rejected
    'publish_targets' => ['airbnb', 'sahibinden', 'hepsiemlak'],
    'quality_tier'   => 'premium_plus', // premium_plus | premium | standard | low
    'blocking_issues' => [],
]
```

**Sorun:** Bu çıktı sadece karar verir — gerçek payload üretmez.

---

## 3. Mevcut Portal Entegrasyonları

### 3.1 Portal ID Depolama (Sağ Passif)
`Ilan` modelinde portal ID'leri **depolanıyor** ama **kullanılmıyor:**

```php
// Ilan.php $fillable
'sahibinden_id', 'emlakjet_id', 'hepsiemlak_id',
'zingat_id', 'hurriyetemlak_id', 'portal_pricing',
```

Bunlar sadece harici sistemlerden gelen ID'leri eşleştirmek için kullanılıyor.

### 3.2 PortalIdNormalizer
```php
// app/Services/Portal/PortalIdNormalizer.php
PortalIdNormalizer::normalize(string $platform, string $id): string
```
Sadece ID formatını normalize eder — API çağrısı yapmaz.

### 3.3 Takvim Senkronizasyonu (Ayrı Akış)
```php
// app/Services/CalendarSyncService.php
CalendarSyncService::syncCalendar($ilanId, $platform) // airbnb, booking_com, google_calendar
```
Takvim senkronizasyonu **çalışıyor** ama sadece okuma (iCal feed) — yazma yok.

### 3.4 Portalsa ID Lookup
```php
// app/Services/IlanReferansService.php
// Sadece cross-referencing için — portal arama değil
```

---

## 4. Workspace Lifecycle State Machine

```php
// WorkspaceState enum
DRAFT → WORKSPACE_CREATED → MEDIA_READY → DESCRIPTION_READY
      → QUALITY_CHECKED → READY_FOR_PUBLISH → PUBLISHED → ACTIVE → ARCHIVED
```

**Publish geçişleri:**
```php
QUALITY_CHECKED  → READY_FOR_PUBLISH  (PublishDecisionAgent approved)
QUALITY_CHECKED  → MEDIA_READY       (fallback)
READY_FOR_PUBLISH → PUBLISHED       (manual/system publish)
PUBLISHED        → ACTIVE            (go live)
```

`READY_FOR_PUBLISH` state'una ulaşılıyor — ancak bu state'tan sonra **yayınlama kodu yok**.

---

## 5. İlan Veri Yapısı (Kaynak)

### 5.1 İlan Core
```
Ilan modeli: baslik, aciklama, fiyat, para_birimi, yayin_durumu
             il_id, ilce_id, mahalle_id
             ana_kategori_id, alt_kategori_id, yayin_tipi_id
```

### 5.2 Konum
```
il (Il modeli): il_adi, il_plaka_kodu
ilce (Ilce modeli): ilce_adi
mahalle: mahalle_adi
lat, lng (koordinatlar)
```

### 5.3 Özellikler
```
ozellikler(): BelongsToMany → Feature tablosu
ekstra_ozellikler: JSON — dinamik key-value
ozellikler için slug/label eşleşmesi — AI Vision'a input olarak gidebilir
```

### 5.4 Medya
```
fotograflar(): HasMany IlanFotografi
  → oda_turu, oda_turu_guven, kalite_puani, kalite_ayrinti
  → vision_data (JSON — AI Vision çıktısı — Sprint 6.4)
  → hero_skoru, media_data (JSON)
kapak_fotografi: bool
```

### 5.5 Kiralama (Yazlık/Turizm)
```
min_stay_nights, max_stay_nights, check_in_time, check_out_time
max_guests, cleaning_fee, security_deposit
```

---

## 6. AI Vision Çıktıları (Sprint 6.4)

### 6.1 VisionAnalysisDTO
```php
// app/DTOs/Vision/VisionAnalysisDTO.php
new VisionAnalysisDTO(
    fotograf_id: int,
    objects: VisionObjectDTO[],        // Tespit edilen nesneler
    rooms: VisionObjectDTO[],          // Oda türleri
    furniture: VisionObjectDTO[],      // Mobilyalar
    amenities: VisionObjectDTO[],       // Ameniteler
    luxuryFeatures: VisionObjectDTO[], // Lüks özellikler
    views: VisionObjectDTO[],          // Manzaralar
    architecturalStyles: VisionObjectDTO[], // Mimari stil
    ai_quality_score: int,           // 0–100
    ai_quality_breakdown: [...],      // composition, luxury_appeal, marketability
    overall_confidence: float,        // 0.0–1.0
    provider: string,                 // 'openai' | 'mock'
    final_room_type: string,          // 'living_room' | 'pool' | ...
    fusion_confidence: float,
    error: ?string,
)
```

### 6.2 PublishingMediaDTO
```php
// app/DTOs/Vision/PublishingMediaDTO.php
new PublishingMediaDTO(
    ilan_id: int,
    hero_fotograf_id: ?int,
    photo_order: int[],               // Sıralı fotoğraf ID'leri
    title_hints: string[],            // AI başlık önerileri
    photo_captions: [...],           // Foto başlık/alt bilgisi
    room_metadata: [...],             // Oda bazlı AI metadata
    is_publishing_ready: bool,
    readiness_issues: string[],
    detected_rooms: [...],           // Tespit edilen odalar
    detected_amenities: string[],
    detected_luxury_features: string[],
    vision_score: int,               // 0–100 aggregate
    avg_ai_confidence: float,
)
```

### 6.3 AI Vision → Ilan persistence
```php
// VisionOrchestrator::persistIlanLevel()
$ilan->forceFill([
    'vision_score' => $publishing->vision_score,
    'vision_ai_confidence' => $publishing->avg_ai_confidence,
    'vision_rooms' => json_encode($aggregated['oda_dagilimi']),
    'vision_amenities' => json_encode(array_keys(...)),
    'vision_luxury' => json_encode(array_keys(...)),
    'vision_media' => json_encode($publishing->toArray()),
])->saveQuietly();
```

---

## 7. Publish Karar Verisi

### PublishDecisionAgent çıktısı (inline — DTO değil)
```php
[
    'decision' => 'approved'|'needs_review'|'rejected',
    'publish_targets' => ['airbnb', 'sahibinden', 'hepsiemlak'],
    'quality_tier' => 'premium_plus',
    'overall_score' => 0.0–1.0,
    'blocking_issues' => [...],
]
```

### WorkspaceState → READY_FOR_PUBLISH geçişi
```php
// PublishDecisionAgent
$workspace->markAiAgentComplete('publish_decision_agent', $decision);
```

---

## 8. READY_TO_PUBLISH Sonrası Kod Yok

**Mevcut durum:**
```
READ_TO_PUBLISH state'ına ulaşıldı
        ↓
[YOK: Kanal adapter kodu]
        ↓
[YOK: Payload üretimi]
        ↓
[YOK: API çağrısı]
```

**Portal API çağrıları yok — sadece stub var:**
```php
// CalendarSyncService.php
public function pushToAirbnb(...): array { return ['success' => true]; } // STUB
public function pushToBookingCom(...): array { return ['success' => true]; } // STUB
```

---

## 9. Mevcut API Resource Katmanı

| Resource | Kapsam |
|----------|--------|
| `IlanPublicResource` | Public — başlık, fiyat, konum, fotoğraf |
| `IlanInternalResource` | Admin — tüm alanlar dahil portal ID'ler |
| `IlanListResource` | Mobile — listeleme |
| `IlanDetailResource` | Mobile — detay |

**Sorun:** Resource'lar **okuma** odaklı. Yayınlama için kanal bazlı **yazma payload** üretmiyorlar.

---

## 10. Neler Var, Neler Eksik

### ✅ Olan
- Workspace lifecycle state machine (9 state)
- PublishDecisionAgent (karar veriyor, payload üretmiyor)
- Portal ID depolama (passif)
- Takvim senkronizasyonu (read-only)
- AI Vision çıktıları (Sprint 6.4)
- ReadinessEvaluatorService (puanlama, eşik değil)

### ❌ Olmayan
- Kanal adapter pattern (ChannelAdapter interface + implementasyonları)
- Kanal bazlı payload üretimi
- PublishPackage DTO
- Sahibinden/Hepsiemlak/Airbnb format eşleştirmesi
- Publish payload → Ilan geri yazma (portal ID atama)
- Workspace → PUBLISHED state geçişi tetikleyicisi
- Gerçek portal API çağrıları

---

## 11. Sprint 6.5 İçin Çıkarımlar

1. **Kanal adapter pattern sıfırdan yazılacak** — mevcut kodda yok
2. **Vision çıktıları mevcut** — `PublishingMediaDTO` → adapter'lara input olacak
3. **Ilan modeli kaynak** — tüm veri burada, adapter'lar dönüştürecek
4. **Publish kararı mevcut** — `PublishDecisionAgent` çıktısı adapter'lara yeter
5. **Lifecycle state mevcut** — adapter'lar state'i tetiklemeyecek, sadece payload üretecek
