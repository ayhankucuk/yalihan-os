# Sprint 6.5 Discovery — REUSE MAP

> **Tarih:** 2026-07-09
> **Tip:** Discovery — Mevcut Kod Yeniden Kullanım Haritası

---

## 1. Yeniden Kullanılabilir Bileşenler

### 1.1 Ilan Modeli (Kaynak)
```
Kaynak: app/Models/Ilan.php
Yeniden kullanım: TÜM kanallar için veri kaynağı
Kapsam: başlık, açıklama, fiyat, konum, kategoriler, özellikler, fotoğraflar, kiralanabilirlik bilgileri
```
✅ **Doğrudan kullanılabilir.**

### 1.2 AI Vision Çıktıları
```
Kaynak: VisionOrchestrator::persistIlanLevel()
Kullanım: vision_score, vision_rooms, vision_amenities, vision_luxury, vision_media
Ilan modelinde persist ediliyor — adapter'lar okur, dönüştürür.
```
✅ **Doğrudan kullanılabilir.** Kanal adapter'ları Vision verisini okuyacak.

### 1.3 PublishingMediaDTO
```
Kaynak: app/DTOs/Vision/PublishingMediaDTO.php
Kullanım: hero_fotograf_id, photo_order, detected_rooms, title_hints
```
✅ **Doğrudan kullanılabilir.** Kanal adapter'ları bu DTO'yu input olarak alacak.

### 1.4 PublishDecisionAgent
```
Kaynak: app/Services/Hermes/Handlers/Workflow/PublishDecisionAgent.php
Yeniden kullanım: publish_targets, quality_tier, decision
Kanalların kararı PublishDecisionAgent veriyor — adapter'lar bunu okur.
```
⚠️ **Kısmi kullanım:** Agent çıktısı adapter'lara giriş olacak. Ancak agent'ın
kendi ürettiği karar yapısı (inline array) yerine typed PublishingDecisionDTO gerekli.

### 1.5 WorkspaceState Enum
```
Kaynak: app/Domain/Workspace/Enums/WorkspaceState.php
Kullanım: READY_FOR_PUBLISH → PUBLISHED geçişi için
```
✅ **Doğrudan kullanılabilir.** Adapter'larda state geçişi tetiklenmeyecek —
sadece read-only kullanılacak.

### 1.6 ReadinessEvaluatorService
```
Kaynak: app/Services/Workspace/ReadinessEvaluatorService.php
Kullanım: WorkspaceSummaryService'deki readines puanlaması
```
⚠️ **Dolaylı kullanım:** Adapter'lar doğrudan kullanmayacak ama cockpit'te görünürlük için gerekli.

### 1.7 PublishDecisionAgent Çıktı Modeli
```
Kaynak: app/Models/PortfolioDriveWorkspace.php
Kullanım: markAiAgentComplete() → autoAdvanceLifecycle() → READY_FOR_PUBLISH
```
⚠️ **Yeniden kullanılamaz:** Agent çıktısı inline array — typed PublishingDecisionDTO gerekli.

---

## 2. Adapter Pattern — Kullanılacak Şablon

### 2.1 Channel Adapter Interface
```php
// app/Services/Publishing/Contracts/ChannelAdapterContract.php
interface ChannelAdapterContract
{
    public function name(): string;              // 'airbnb' | 'sahibinden' | 'hepsiemlak'
    public function supports(Ilan $ilan): bool; // Kategori/tip kontrolü
    public function buildPayload(
        Ilan $ilan,
        PublishingMediaDTO $vision,
        PublishingDecision $decision,
    ): ChannelPayload;
    public function validatePayload(ChannelPayload $payload): ValidationResult;
}
```

### 2.2 Channel Payload
```php
// app/DTOs/Publishing/ChannelPayload.php
final class ChannelPayload
{
    public function __construct(
        public readonly string $channel,        // 'airbnb' | 'sahibinden' | 'hepsiemlak'
        public readonly Ilan $ilan,
        public readonly array $mappedFields,    // Kanal-özgü alan eşleşmeleri
        public readonly array $photos,          // Sıralı fotoğraflar
        public readonly array $seo,            // Başlık, açıklama, meta
        public readonly array $pricing,        // Fiyatlandırma
        public readonly array $raw,            // Ham çıktı (debug)
        public readonly ?string $validationError = null,
    ) {}
}
```

---

## 3. Yeniden Kullanılmayacak Kodlar

| Kod | Neden Yeniden Kullanılamaz |
|-----|--------------------------|
| `CalendarSyncService::pushToAirbnb()` | Sadece Takvim senkronizasyonu — farklı endpoint'ler |
| `IlanPublicResource` | Read-only, kanal dönüşümü yok |
| `IlanInternalResource` | Read-only, kanal dönüşümü yok |
| `PortalIdNormalizer` | Sadece ID format — API çağrısı yok |
| `IlanReferansService` | Sadece arama/cross-reference |

---

## 4. Sprint 6.4 Sonrası Kullanılabilir Veriler

### 4.1 Ilan Modeli Üzerindeki Vision Verileri
```php
$ilan->vision_score          // int 0–100
$ilan->vision_ai_confidence // float 0.0–1.0
$ilan->vision_rooms          // array ['Salon' => 2, 'Havuz' => 1]
$ilan->vision_amenities     // array ['Klima', 'WiFi', 'Otopark']
$ilan->vision_luxury        // array ['Mermer', 'Özel Havuz']
$ilan->vision_media         // array (PublishingMediaDTO serialization)
```

### 4.2 AI Vision Orchestrator Pipeline
```
AnalyzeVisionJob::dispatch($ilanId)
        ↓
VisionOrchestrator::analyzeIlan($ilanId)
        ↓
VisionAnalysisDTO[] (per photo)
        ↓
PublishingMediaDTO (ilan-level aggregate)
        ↓
persist → Ilan model (vision_* kolonları)
```
Adapter'lar `vision_media` JSON'ı okuyup dönüştürecek.

---

## 5. Adapter Giriş/Çıktı Haritası

```
┌─────────────────────────────────────────────────────────┐
│  KAYNAK: Ilan Model                                   │
│  • baslik, aciklama, fiyat                           │
│  • vision_media (PublishingMediaDTO JSON)               │
│  • vision_rooms, vision_amenities, vision_luxury       │
│  • fotograflar (ordered)                             │
└────────────────┬──────────────────────────────────────┘
                 │ READ BY adapter (read-only, ¥ TenantScope)
                 ↓
┌─────────────────────────────────────────────────────↓
│  ChannelAdapterContract::buildPayload()                  │
│  Inputs: Ilan + PublishingMediaDTO + PublishingDecision │
└────────────────┬──────────────────────────────────────┘
                 │ OUTPUTS
     ┌────────────┴────────────┬────────────────────┐
     ↓                         ↓                     ↓
 AirbnbPayload         SahibindenPayload       HepsiemlakPayload
     (airbnb_format)         (sahibinden_format)    (hepsieemlak_format)
```

---

## 6. Adapter BuildPayload Giriş Detayı

### 6.1 Airbnb Payload İçin Gerekli Veriler
```
Ilan kaynak:
  baslik, aciklama
  fiyat → Airbnb fiyat formatına
  net_m2, oda_sayisi, banyo_sayisi
  min_stay_nights, max_guests
  fotoğraflar → sıralı URL'ler
  vision_media.hero_fotograf_id → listing_photo_id

PublishingMediaDTO kaynak:
  detected_rooms → space_type
  detected_amenities → amenities[]
  vision_luxury → highlights[]
  title_hints → name
  photo_captions → description_snippets[]
```

### 6.2 Sahibinden Payload İçin Gerekli Veriler
```
Ilan kaynak:
  baslik, aciklama
  fiyat
  il/ilce/mahalle → sahibinden konum formatı
  oda_sayisi, net_m2
  fotoğraflar → sıralı URL'ler

PublishingMediaDTO kaynak:
  detected_rooms → kategori eşleşmesi
  vision_media → ilan tipi
```

### 6.3 Hepsiemlak Payload İçin Gerekli Veriler
```
Ilan kaynak:
  baslik, aciklama
  fiyat
  konum
  özellikler → hepsiemlak özellik formatı

PublishingMediaDTO kaynak:
  vision_score → ilan kalite puani
  detected_rooms → oda tipi
```
