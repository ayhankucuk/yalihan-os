# Sprint 6.5 Discovery — GAP ANALYSIS

> **Tarih:** 2026-07-09
> **Tip:** Discovery — Gap Analizi

---

## 1. Gap Özeti

| Gap | Öncelik | Açıklama |
|-----|---------|-----------|
| G-01 | 🔴 Kritik | ChannelAdapterContract yok — adapter pattern için interface gerekli |
| G-02 | 🔴 Kritik | Airbnb/Sahibinden/Hepsiemlak adapter implementasyonları yok |
| G-03 | 🔴 Kritik | PublishingPackage DTO yok — kanal bağımsız payload üretilemiyor |
| G-04 | 🟠 Yüksek | PublishDecisionAgent inline array çıktısı — typed PublishingDecisionDTO gerekli |
| G-05 | 🟠 Yüksek | PublishDecisionAgent çıktısı adapter'lara iletilmiyor |
| G-06 | 🟡 Orta | Workspace → PUBLISHED state geçiş trigger'ı yok |
| G-07 | 🟡 Orta | AI başlık/açıklama önerileri yok (Sprint 6.4'te title_hints var ama transformer yok) |
| G-08 | 🟡 Orta | Çok dilli içerik desteği yok |
| G-09 | 🟢 Düşük | Kanal bazlı doğrulama kuralları belgelenmemiş |
| G-10 | 🟢 Düşük | Workspace cockpiti'nde kanal bazlı durum gösterimi yok |

---

## 2. Gap Detayları

### G-01 — ChannelAdapterContract Yok 🔴

**Tanım:** Adapter pattern için temel interface mevcut değil.

**Etki:** Her kanal için ayrı kod yazılırsa mimari bütünlüğü bozulur.

**Çözüm:** Sprint 6.5'te yazılacak.

```php
// Yazılacak
interface ChannelAdapterContract {
    public function name(): string;
    public function supports(Ilan $ilan): bool;
    public function buildPayload(Ilan $ilan, PublishingMediaDTO $vision): ChannelPayload;
    public function validate(ChannelPayload $payload): ValidationResult;
}
```

---

### G-02 — Adapter İmplementasyonları Yok 🔴

**Tanım:** Airbnb, Sahibinden, Hepsiemlak için gerçek payload builder yok.

**Mevcut:** Sadece `CalendarSyncService`'de stub `pushToAirbnb()` vb. var (çalışmıyor).

**Etki:** Yayınlama yapılamıyor.

**Çözüm:** Sprint 6.5'te adapter'lar yazılacak. İlk aşamada adapter sadece payload üretir (API çağrısı sonraki sprintlerde).

**Stub → Real adapter:**
```
Sprint 6.5: Adapter.buildPayload() → ChannelPayload (object)
Sprint 6.6+: Adapter.publish(ChannelPayload) → HTTP API çağrısı
```

---

### G-03 — PublishingPackage DTO Yok 🔴

**Tanım:** Kanallar arası ortak payload üretilemiyor.

**Etki:** Her kanal için ayrı DTO + Transformer gerekli.

**Çözüm:** PublishingPackageDTO yazılacak.

```php
// Yazılacak
final class PublishingPackage {
    public function __construct(
        public readonly Ilan $ilan,
        public readonly PublishingMediaDTO $vision,    // Sprint 6.4
        public readonly array $airbnbPayload,           // Airbnb formatı
        public readonly array $sahibindenPayload,     // Sahibinden formatı
        public readonly array $hepsiemlakPayload,     // Hepsiemlak formatı
        public readonly array $validationErrors = [],
    ) {}
}
```

---

### G-04 — PublishDecisionAgent Inline Array 🔴

**Tanım:** `PublishDecisionAgent` typed DTO yerine inline array döner.

**Mevcut:**
```php
return [
    'decision' => 'approved',
    'publish_targets' => ['airbnb', 'sahibinden', 'hepsiemlak'],
    'quality_tier' => 'premium',
];
```

**Etki:** Type-safe erişim yapılamıyor, IDE autocomplete çalışmıyor.

**Çözüm:** PublishingDecision DTO yazılacak.

---

### G-05 — PublishDecisionAgent Çıktısı Adapter'lara İletilmiyor 🟠

**Tanım:** PublishDecisionAgent karar verir ama bunu okuyan adapter yok.

**Etki:** Adapter'lar hangi kanallara publish edeceğini bilmiyor.

**Çözüm:** PublishDecisionAgent event'ini adapter'lar dinleyecek veya service'den okuyacak.

---

### G-06 — Workspace PUBLISHED State Trigger'ı Yok 🟡

**Tanım:** `READY_FOR_PUBLISH → PUBLISHED` geçişini tetikleyen kod yok.

**Etki:** Workspace state'i yayınlandıktan sonra güncellenmiyor.

**Çözüm:** Adapter publish başarılı olunca Workspace lifecycle güncelleyecek (Sprint 6.6+).

---

### G-07 — AI İçerik Öneri Transformer'ı Yok 🟡

**Tanım:** `PublishingMediaDTO.title_hints` mevcut ama kanal formatına dönüştüren yok.

**Etki:** AI önerilen başlık/açıklama adapter'lara ulaşmıyor.

**Çözüm:** `TitleTransformer` ve `DescriptionTransformer` yazılacak.

---

### G-08 — Çok Dilli İçerik Desteği Yok 🟡

**Tanım:** İlan sadece Türkçe üretiliyor.

**Etki:** Yabancı portal'larda (Airbnb EN, Booking.com) içerik yetersiz.

**Çözüm:** Dil desteği extension olarak planlanacak (Sprint 6.6+).

---

### G-09 — Kanal Kuralları Belgelenmemiş 🟢

**Tanım:** Airbnb, Sahibinden, Hepsiemlak'ın zorunlu alanları, karakter sınırları, formatları bilinmiyor.

**Etki:** Adapter yazarken eksik alan fark edilemeyebilir.

**Çözüm:** Sprint 6.5'te kanalların dokümantasyonu yazılacak.

---

### G-10 — Cockpit'te Kanal Durumu Yok 🟢

**Tanım:** Workspace cockpit'inde "hangi kanala publish edildi" gösterilmiyor.

**Etki:** Advisor durumu göremez.

**Çözüm:** Cockpit panel extension (Sprint 6.5 sonrası).

---

## 3. Mimari Boşluklar

```
                    READY_FOR_PUBLISH
                           │
                           ▼
              ┌──────────────────────────┐
              │ PublishDecisionAgent    │ ← mevcut (karar veriyor)
              │ publish_targets[]        │
              └──────────┬─────────────┘
                          │ çıktı okunmuyor
                          ▼
              ┌──────────────────────────┐
              │ ChannelAdapterRegistry  │ ← YOK
              │ Adapter'ları seçer     │
              └──────────┬─────────────┘
                          │ her kanal için buildPayload()
         ┌────────────────┼────────────────┐
         ▼                ▼                ▼
   ┌──────────┐    ┌──────────────┐   ┌──────────────┐
   │ Airbnb   │    │ Sahibinden   │   │ Hepsiemlak  │
   │ Adapter │    │ Adapter     │   │ Adapter    │
   │ (YOK)  │    │ (YOK)      │   │ (YOK)     │
   └────┬─────┘    └──────┬──────┘   └──────┬──────┘
        │                  │                │
        ▼                  ▼                ▼
   ChannelPayload    ChannelPayload    ChannelPayload
```

---

## 4. İlk Sprint Hedefi (Payload Üretimi)

Sprint 6.5 hedefi sadece **payload üretimi** — API çağrısı sonraki sprintlere.

```
Adapter::buildPayload()
  │
  ├── AirbnbAdapter → AirbnbFormatDTO
  ├── SahibindenAdapter → SahibindenFormatDTO
  └── HepsiemlakAdapter → HepsiemlakFormatDTO
```

---

## 5. Sprint 6.5 Sonunda Olacak/Olmayacak

| Olacak | Olmayacak |
|--------|-----------|
| ChannelAdapterContract | Gerçek API çağrısı |
| Airbnb/Sahibinden/Hepsiemlak Adapter | Kanal bazlı retry logic |
| PublishingPackage DTO | Rate limiting |
| ChannelPayload DTO | Kanal kotaları |
| TenantScope korunması | Çok dilli içerik |
| Replay-safe job | Kanal spesifik hata yönetimi |
| Publish kararı → Adapter'a iletimi | Workspace PUBLISHED state güncellemesi |
