# Sprint 14 Charter — Property Command Center

**Sprint:** 14
**Epic:** Property Command Center — Unified Property Operations Dashboard
**Author:** SAAB
**Date:** 2026-07-30
**ERA V Phase:** Phase 2 — Autonomous Operations
**Predecessor:** Sprint 13 (Channel Manager)
**Reference:** BR-20260729-ERAV001

---

## Exit Question

> **"Bir property'nin günlük operasyonları tek bir ekrandan yönetilebiliyor mu?"**

Bu soru Sprint 14'ün tüm tasarım kararlarını yönlendirmeli.

---

## Context

Sprint 13, Channel Manager altyapısını kurdu — canonical availability senkronizasyonu, Airbnb adapter mimarisi, immutable audit trail. Ancak bu yetenekler **görünür değil**: bir danışman, mevcut arayüzlerden bu verilere erişemiyor.

Sprint 14, Sprint 13'ün üzerine inşa ederek mevcut capability'leri tek bir operasyon merkezinde birleştirir. **Yeni servis yazmak yerine mevcut servisleri görünür ve kullanılabilir hale getirir.**

---

## 4-Gate Exit Criteria

Her gate, Sprint 13 formatında kanıt gerektirir:

| Gate | Soru | Kanıt |
|------|------|-------|
| **G-01** | Çalışan capability | Feature test + manual verification |
| **G-02** | Test kanıtı | `php artisan test` — feature + regression |
| **G-03** | Operasyonel kanıt | Log / API response / screenshot |
| **G-04** | Business Automation Impact | Manuel adım: 7 → 2; süre: 12dk → 45sn |

---

## Epic Breakdown

### E01 — Property Command Center Aggregate / View Model

**Hedef:** Tek bir property için tüm operasyonel verileri toplayan aggregate + read model.

**Sorumluluklar:**
- `PropertyCommandAggregate` — property bazlı tüm veri toplayıcı
- `PropertyCommandView` — tek property'nin operasyonel konsol read model
- Tenant isolation korunur — sadece yetkili kullanıcının property'leri

**Owned Data:**
```
Property Identity
• id, baslik, il, ilce, mahalle
• Sahibi (owner) bilgisi
• Temsilci (danışman) bilgisi
• Durum (yayin_durumu, aktiflik_durumu)

Availability (Sprint 13 entegrasyonu)
• Mevcut müsaitlik aralıkları
• Son senkronizasyon zamanı
• Channel sync executions (son 10)
• Channel health (per kanal)

Reservations
• Aktif rezervasyonlar
• Yaklaşan rezervasyonlar
• Sonlanan rezervasyonlar (son 30 gün)

Listings
• Per kanal: yayin_durumu, external_id, last_synced_at
• Kanal: Airbnb, Sahibinden, Booking.com

Operations
• Açık görevler (action items)
• Son execution (ChannelSyncExecution)
• Retry gereken işler (failed executions)

Timeline
• Domain Events (son 20)
• Publication history
• Reservation history
• Sync history
```

**Exit:** Aggregate tek DB sorgusunda (veya 2-3 cached query) tüm bu verileri döner.

---

### E02 — Reservation & Availability Panel

**Hedef:** Property Command Center'da rezervasyon ve müsaitlik paneli.

**Sorumluluklar:**
- `PropertyAvailabilityController` — API endpoint'leri
- `PropertyAvailabilityPanel` blade view
- Sprint 13 `AvailabilitySynchronizationService` entegrasyonu

**Panel İçeriği:**
```
┌─────────────────────────────────────────┐
│ MÜSAİTLİK YÖNETİMİ                      │
├─────────────────────────────────────────┤
│ [Rezervasyon Ekle] [Blok Ekle] [Senk.] │
├─────────────────────────────────────────┤
│ Takvim Görünümü                          │
│ (müsait/rezerve/bloklu günler)          │
├─────────────────────────────────────────┤
│ Kanal Senkronizasyonu                    │
│ • Airbnb: ● Son sync 5 dk önce           │
│ • Sahibinden: ● Son sync 2 saat önce     │
└─────────────────────────────────────────┘
```

**API Endpoints:**
```
GET    /api/property/{id}/command/availability
POST   /api/property/{id}/command/reservation
POST   /api/property/{id}/command/block
POST   /api/property/{id}/command/sync
GET    /api/property/{id}/command/sync-history
```

**Exit:** Rezervasyon ekle → canonical DB yaz → SyncAvailabilityJob tetiklenir (Sprint 13 chain).

---

### E03 — Listing & Publication Status

**Hedef:** Property'nin tüm kanallardaki yayın durumunu tek panelde göstermek.

**Sorumluluklar:**
- `PropertyListingController` — API endpoint'leri
- `PropertyListingPanel` blade view
- Mevcut `IlanYayinService` / `IlanYayinDurumu` entegrasyonu

**Panel İçeriği:**
```
┌─────────────────────────────────────────┐
│ YAYIN DURUMU                            │
├─────────────────────────────────────────┤
│ ● Airbnb         Yayında    [Yönet]    │
│ ● Sahibinden     Yayında    [Yönet]    │
│ ● Booking.com    Beklemede   [Yönet]    │
├─────────────────────────────────────────┤
│ [Tümünü Yayınla] [Tümünü Kaldır]       │
└─────────────────────────────────────────┘
```

**Exit:** Danışman her kanalın durumunu tek ekrandan görebilir.

---

### E04 — Timeline & Execution History

**Hedef:** Property'nin tüm domain event ve execution geçmişini timeline olarak göstermek.

**Sorumluluklar:**
- `PropertyTimelineController` — API endpoint'leri
- `PropertyTimelinePanel` blade view
- `ChannelSyncExecution` + `HermesEventLog` + `DomainEvent` query'leri

**Timeline Entry Türleri:**
```
• Reservation Created — "Rezervasyon oluşturuldu" — user, timestamp
• Reservation Confirmed — "Rezervasyon onaylandı" — user, timestamp
• Listing Published — "Airbnb'de yayınlandı" — channel, timestamp
• Listing Unpublished — "Airbnb'den kaldırıldı" — channel, timestamp
• Sync Executed — "Kanal senkronizasyonu çalıştı" — result, duration
• Sync Failed — "Senkronizasyon hatası" — error_type, retry_count
• Availability Blocked — "Blokaj eklendi" — date_range, reason
• Availability Opened — "Blokaj kaldırıldı" — date_range
```

**Pagination:** Infinite scroll veya "son 20" + "daha fazla yükle" butonu.

**Exit:** Danışman bir property'nin tüm geçmişini kronolojik sırayla görebilir.

---

### E05 — Command Actions

**Hedef:** Panel üzerinden temel operasyonları başlatmak.

**Komutlar:**
```
POST /api/property/{id}/command/publish      — Tüm kanallarda yayınla
POST /api/property/{id}/command/unpublish    — Tüm kanallardan kaldır
POST /api/property/{id}/command/sync         — Manuel senkronizasyon tetikle
POST /api/property/{id}/command/sync/{channel} — Belirli kanal senkronizasyonu
POST /api/property/{id}/command/reserve      — Manuel rezervasyon oluştur
POST /api/property/{id}/command/block        — Manuel blokaj ekle
```

**UI:** Alpine.js ile optimistic UI güncellemesi + toast notification.

**Exit:** Danışman tek tıkla kanal yayınlama/senkronizasyon başlatabilir.

---

### E06 — Certification & Evidence

**Hedef:** Sprint 14'ü SAAB'a sunmak için tüm kanıtları paketlemek.

**Çıktılar:**
```
docs/ERA_V/
├── Phase_Reports/
│   └── SPRINT-14-CERTIFICATION.md
└── Evidence/sprint-14/
    ├── G-01-CAPABILITY-EVIDENCE.md
    ├── G-02-TEST-EVIDENCE.md
    ├── G-03-OPERATIONAL-EVIDENCE.md
    ├── G-04-BAI-EVIDENCE.md
    └── BUSINESS-OPERATION-CARD.md
```

---

## Route Planı

```
# Web (admin panel)
GET    /admin/property/{id}/command-center   → PropertyCommandController@index

# API v1
GET    /api/v1/property/{id}/command         → PropertyCommandController@show
GET    /api/v1/property/{id}/command/availability
POST   /api/v1/property/{id}/command/reservation
POST   /api/v1/property/{id}/command/block
POST   /api/v1/property/{id}/command/sync
POST   /api/v1/property/{id}/command/publish
POST   /api/v1/property/{id}/command/unpublish
GET    /api/v1/property/{id}/command/timeline
GET    /api/v1/property/{id}/command/listings
```

---

## Mevcut Servisler Entegrasyonu (Yeniden YazılmayACAK)

| Mevcut Servis | Kullanım Yeri |
|---------------|--------------|
| `AvailabilitySynchronizationService` | E02 — sync butonu |
| `ChannelSyncExecution` model | E04 — sync history |
| `IlanYayinService` | E03 — listing durumu |
| `IlanReservation` model | E02 — rezervasyon listesi |
| `IlanTakvimSync` model | E02 — kanal eşleşmesi |
| `HermesEventLog` | E04 — domain event history |
| `PropertyAvailability` model | E02 — müsaitlik aralıkları |

---

## Business Operation Automated

```
ÖNCE:
• Property durumunu görmek için 4-5 farklı sayfa aç
• Airbnb takvimini manuel kontrol et
• Sahibinden ilanını manuel kontrol et
• Rezervasyonları manuel listele
• Sync durumunu görmek için log'lara bak

SONRA:
• Tek sayfa: Property Command Center
• Tüm bilgiler tek ekranda
• Tek tıkla operasyon başlat
```

**Automation Impact:**
- Manuel adım: 7 → 2 (property seç + operasyon seç)
- Ortalama süre: ~12 dk → ~45 sn
- İnsan müdahalesi: %100 → ~20%

---

## Non-Goals (Sprint 14 Kapsamı Dışında)

- Yeni domain logic yazmak (mevcut capability'leri sarf etmek)
- Mobil UI
- Çoklu property karşılaştırma görünümü
- Gerçek zamanlı WebSocket updates
- Airbnb/Booking API entegrasyonu (Sprint 13 S13-CD-002)
- AI öneri motoru (Sprint 16)

---

## Dependencies

| Dependency | Kaynak | Önkoşul |
|-----------|--------|---------|
| ChannelSyncExecution model | Sprint 13 | Zorunlu |
| AvailabilitySyncAggregate | Sprint 13 | Zorunlu |
| IlanYayinService | Mevcut | Zorunlu |
| PropertyAvailability model | Mevcut | Zorunlu |
| Airbnb API credentials | Airbnb | Opsiyonel (S13-CD-002) |

---

## Debt from Sprint 13

| ID | Konu | Sprint 14 etkisi |
|----|------|-----------------|
| S13-CD-001 | 4 skipped integration tests | E02 test yazarken MySQL gerekecek |
| S13-CD-002 | Airbnb API yok | Airbnb panel "sandbox" modunda gösterilir |
| S13-CD-003 | Production BAI yok | Sprint 14 G-04 internal ölçüm yapılabilir |

---

## Success Metrics

| Metrik | Hedef |
|--------|-------|
| Panel yüklenme süresi | < 800ms |
| DB query sayısı (command aggregate) | ≤ 5 |
| Feature test coverage | ≥ 20 tests |
| Sprint 13'e regresyon | 0 |
| G-01 / G-02 / G-03 / G-04 | Tümü PASS |
