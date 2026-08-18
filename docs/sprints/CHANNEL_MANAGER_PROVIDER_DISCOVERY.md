# CHANNEL_MANAGER_PROVIDER_DISCOVERY — Discovery Charter

**Status:** 🟡 DISCOVERY  
**Charter Date:** 2026-08-10  
**SAAB Authorization:** SAAB Board (Wave 4 Finance closure)  
**Prerequisite:** ✅ CHANNEL_MANAGER Wave 1 CERTIFIED (12874e9)  
**Scope:** Architecture only — no implementation

---

## Mission

YALIHAN'ın kendi canonical availability sistemi ile Airbnb / Booking.com gibi dış kanallar
arasında güvenilir, provider-neutral bir köprü kurmak.

Airbnb direct API erişiminin mevcut durumda bloke olduğu bilinmektedir (EX-001 pilot kanıtı).
Bu nedenle bu Discovery, Channex veya eşdeğer bir intermediary üzerinden provider-neutral
entegrasyonun mimari sınırlarını, idempotency şemasını, availability sync boundary'sini,
pricing sync boundary'sini ve webhook/inbound reservation ingest sınırını netleştirir.

**SAAB Constraint:** Bu Discovery aşamasında hiçbir Channex API çağrısı yapılmaz, hiçbir
credential kaydedilmez, hiçbir production bağlantısı kurulmaz.

---

## SAAB Başarı Sorusu

> "YALIHAN, Channex (veya eşdeğer provider) üzerinden Airbnb ve Booking.com kanallarıyla
> provider-neutral, tenant-isolated, idempotent ve availability-canonical biçimde
> entegre olabilecek mimari sınırları belirleyebiliyor mu?"

---

## Kapsam İçi

1. **Provider-neutral contract analizi**
   - Mevcut `ChannelSyncContract` Channex model ile uyumlu mu?
   - Hangi metodlar eksik/fazla?

2. **Channex API capability haritası** (public docs'tan)
   - Availability sync (push/pull)
   - Rate/pricing sync boundary (kapsam dışı mı?)
   - Reservation ingest (webhook / polling)
   - Listing mapping (Channex listing_id → YALIHAN property_id)

3. **Idempotency key şeması**
   - Channex correlation ID formatı
   - Duplicate delivery önleme

4. **Webhook / inbound reservation ingest sınırı**
   - Channex → YALIHAN reservation creation: bu Wave mı, Wave 2 mi?
   - Reservation ingest → ReservationService → canonical chain

5. **Availability sync boundary**
   - YALIHAN → Channex → Airbnb/Booking push
   - Airbnb/Booking → Channex → YALIHAN pull (iCal veya API)
   - PropertyAvailability SSOT'u kim sorgular?

6. **Tenant isolation**
   - Channex API key: tenant başına mı, global mı?
   - IlanTakvimSync tablosunun yeterliliği

7. **Provider portability**
   - Eğer Channex yerine başka intermediary seçilirse ne değişir?
   - Değişmemesi gereken sınırlar neler?

8. **ADR-006 taslağı**
   - Channel Manager Provider Architecture kararı

## Kapsam Dışı

- Channex API gerçek entegrasyon kodu
- Booking.com direct API
- Airbnb direct API
- Pricing / rate management
- Payment processing
- Review / messaging (EX-001 domain'i)
- VRBO / HomeAway entegrasyonu

---

## Mevcut Altyapı Analizi

### Wave 1'den Gelen Varlıklar

| Bileşen | Durum | Not |
|---------|-------|-----|
| `ChannelSyncContract` | ✅ Var | `pushAvailability`, `pullAvailability`, `testConnection` |
| `ICalAdapter` | ✅ Var | iCal push/pull çalışıyor |
| `AirbnbChannelAdapter` | ⚠️ Eski interface | `ChannelAdapter` implement ediyor, `ChannelSyncContract` değil |
| `ChannelSyncExecution` model | ✅ Var | Audit trail |
| `RetryPolicy` | ✅ Var | Exponential backoff |
| `ChannelSyncResponse` DTO | ✅ Var | Success/failure/partial |
| `IlanTakvimSync` model | ✅ Var | `external_listing_id`, `platform`, credentials |
| `Channel` enum | ✅ Var | airbnb, booking, ical, vrbo, manual |

### Kritik Gap: AirbnbChannelAdapter ↔ ChannelSyncContract Uyumsuzluğu

`AirbnbChannelAdapter` eski `ChannelAdapter` interface'ini implement ediyor:
```php
class AirbnbChannelAdapter implements ChannelAdapter  // ESKI
```

Wave 1'de oluşturulan yeni contract:
```php
interface ChannelSyncContract  // YENİ — tenant_id, correlationId parametreleri eklendi
{
    public function pushAvailability(int $tenantId, int $propertyId, string $correlationId, array $data): ChannelSyncResponse;
    public function pullAvailability(int $tenantId, int $propertyId, string $correlationId, string $from, string $to): ChannelSyncResponse;
    public function testConnection(int $tenantId): ChannelSyncResponse;
}
```

**Karar Noktası P1:** `AirbnbChannelAdapter` yeni `ChannelSyncContract`'ı implement edecek şekilde
refactor edilmeli mi, yoksa Channex adapter önce yazılıp Airbnb daha sonra mı uyarlanmalı?

---

## Channex Capability Analizi (Public Docs)

### Channex Ne Yapar?

Channex bir **Property Management System (PMS) / OTA Connectivity Hub**'dır:
- Airbnb, Booking.com, Expedia, VRBO gibi OTA'larla API bağlantısı sağlar
- PMS (YALIHAN) → Channex API → OTA yönünde availability/rate push
- OTA → Channex Webhook → PMS yönünde reservation ingest

### Channex API Capability Haritası

| Capability | Channex Destekler mi? | YALIHAN Kapsamı |
|------------|----------------------|-----------------|
| Availability push | ✅ Evet | Wave 2 kapsam adayı |
| Rate/pricing push | ✅ Evet | Kapsam dışı (pricing domain) |
| Reservation ingest webhook | ✅ Evet | Wave 2 veya Wave 3 adayı |
| Listing mapping | ✅ Evet | `IlanTakvimSync.external_listing_id` yeterli |
| Multi-channel (Airbnb + Booking tek API) | ✅ Evet | Ana motivasyon |
| Pull availability from OTA | ✅ Evet (via Channex) | Wave 2 adayı |
| Messaging | ❌ Hayır (OTA messaging ayrı) | EX-001 domain'i |

### Channex Tenant Isolation Modeli

Channex'te her property ayrı bir **Channel Connection** kaydına sahiptir.
YALIHAN tenant modeli: `Tenant → N Ilan → 1 ChannelConnection (per channel)`

`IlanTakvimSync` tablosu bu mapping için yeterlidir:
```
IlanTakvimSync.ilan_id      → YALIHAN property
IlanTakvimSync.platform     → 'channex' (yeni değer eklenecek)
IlanTakvimSync.external_listing_id → Channex property_id
IlanTakvimSync.api_key      → Channex API key (tenant başına)
```

---

## Araştırma Bulguları

### Bulgu 1: Airbnb Direct API Bloke — Channex İzole Eder ✅

EX-001 pilot'u sırasında Airbnb direct API erişiminin bloke olduğu kanıtlandı.
Channex bu bloğu bypass eder: Channex kendi Airbnb ortaklığı üzerinden entegre olur.
YALIHAN → Channex API → Airbnb zinciri, direct Airbnb API gerektirmez.

### Bulgu 2: ChannelSyncContract Yeterince Soyut ✅ (Küçük Uyarlama Gerekli)

Mevcut `ChannelSyncContract` Channex modeline büyük ölçüde uygundur.
Tek gerekli ekleme: `getChannel()` metodunun `Channel::CHANNEX` değeri dönmesi için
`Channel` enum'una yeni case eklenmesi.

Tercih: Channex, transport layer olarak kullanılır. `Channel` enum değerleri hedef OTA'yı
temsil eder (airbnb, booking). Channex sadece transport provider'dır, channel değil.

**ADR-006 Kararı:** `ChannelSyncContract` OTA kanallarını temsil eder.
Channex bir transport provider'dır — `Channel` enum'una eklenmez.
`AirbnbChannelAdapter` ve `BookingChannelAdapter` Channex HTTP client'ını kullanır.

### Bulgu 3: Webhook Ingest Sınırı — Wave 2'ye Ertelenir

Channex, rezervasyon oluştuğunda YALIHAN'a webhook gönderir.
Bu webhook'u `ReservationService.createReservation()` zincirine bağlamak kritik ama
kapsamlı bir iştir.

**Karar:** Webhook ingest (Channex → YALIHAN reservation) Wave 2 kapsamındadır.
Bu Discovery Wave 1'i (availability push/pull altyapısı) kapsar.

### Bulgu 4: Pricing Sync Kapsam Dışı (Bu Program)

Channex rate push capability'si var ama YALIHAN'ın pricing domain'i ayrı bir capability
(VillaPricingCalculatorService, PropertyPricingService). Bu ikisinin birleştirilmesi
bu program kapsamı dışındadır.

### Bulgu 5: ICalAdapter Channex Ortamında da Çalışır

Channex iCal feed URL destekler. Mevcut `ICalAdapter` bu path'i karşılar.
Channex üzerinden fallback: her OTA bağlantısı kurulamazsa iCal push yedek yol olarak kalır.

---

## Önerilen Mimari

```
YALIHAN Canonical Chain
    │
    ├── PropertyAvailability (SSOT)
    │         ↑ write: CanonicalAvailabilityService
    │         ↓ read: OperationalCalendarService
    │
    └── ChannelSyncContract (provider-neutral)
              │
              ├── AirbnbChannelAdapter (via Channex HTTP)
              │         └── Channex API → Airbnb
              │
              ├── BookingChannelAdapter (via Channex HTTP)
              │         └── Channex API → Booking.com
              │
              ├── ICalAdapter (direct iCal, no Channex)
              │
              └── [Future] VrboChannelAdapter (via Channex)

Channex Layer (transport only)
    ├── ChannexClient (HTTP, credentials, retry)
    ├── ChannexAvailabilityMapper (canonical → Channex API format)
    └── ChannexWebhookHandler (ingest → ReservationService — Wave 2)
```

### Constraint: Channel Manager DOES NOT

| Yasak | Gerekçe |
|-------|---------|
| PropertyAvailability'e doğrudan yazma | CanonicalAvailabilityService tek write path |
| Conflict resolution | ConflictDetectionService owns this |
| Pricing kararı | Pricing domain owns this |
| Credential'ları loglamak | IlanTakvimSync encrypted storage |
| Cross-tenant data | Her adapter çağrısı tenant_id içermeli |

---

## Önerilen İmplementasyon Sırası (Wave Planı)

### Wave 1 (bu Discovery'nin çıktısı — ADR-006)
- `ChannexClient` HTTP client (auth, retry, timeout)
- `ChannexAvailabilityMapper` DTO
- `AirbnbChannelAdapter` → `ChannelSyncContract` uyumu (refactor)
- `BookingChannelAdapter` stub (ChannexClient üzerinden)
- `ChannexChannelAdapter` base class veya trait
- Contract test: tüm adapter'lar `ChannelSyncContract` implement ediyor mu?
- 10 SAAB test

### Wave 2 (ayrı Discovery)
- Channex webhook ingest
- `ChannexWebhookController` → `ReservationService.createReservation()`
- Inbound reservation idempotency
- Webhook signature doğrulama

### Wave 3 (ayrı Discovery)
- Pricing sync (rate push) — pricing domain hazır olunca
- Multi-room / multi-rate capability

---

## ADR-006 Taslağı

**Başlık:** Channel Manager Provider Architecture — Channex as Transport Layer

**Karar:**
1. YALIHAN, Airbnb/Booking.com entegrasyonu için Channex'i transport provider olarak kullanır
2. `ChannelSyncContract` OTA kanallarını temsil eder; Channex transport layer'dır, enum'a eklenmez
3. `AirbnbChannelAdapter` ve `BookingChannelAdapter` mevcut `ChannelSyncContract`'ı implement eder, Channex HTTP client'ını inject olarak alır
4. Direct OTA API bağımlılığı yoktur — Channex bypass ile provider değiştirilebilir
5. Webhook ingest (Channex → YALIHAN reservation) Wave 2 kapsamındadır

**Gerekçe:** Airbnb direct API erişimi bloke. Channex ortaklık üzerinden erişim sağlar.
Provider-neutral contract mimarisi korunarak gelecekte başka intermediary seçilebilir.

---

## SAAB Başarı Kriterleri (Discovery Kapanışı)

| # | Kriter | Durum |
|---|--------|-------|
| D1 | Channex capability haritası tamamlandı | ✅ |
| D2 | `ChannelSyncContract` uyumluluk analizi yapıldı | ✅ |
| D3 | AirbnbChannelAdapter ↔ ChannelSyncContract gap netleşti | ✅ |
| D4 | Webhook ingest sınırı belirlendi (Wave 2) | ✅ |
| D5 | Pricing sync kapsam dışı kararı verildi | ✅ |
| D6 | Tenant isolation modeli doğrulandı | ✅ |
| D7 | ADR-006 taslağı oluşturuldu | ✅ |
| D8 | Wave 1 implementasyon listesi netleşti | ✅ |

---

## Provider Karşılaştırma (2026-08-10 Araştırması)

### Kaynaklar
- Channex: channex.io pricing, docs.channex.io, webhook docs
- Guesty: guesty.com pricing, G2/Capterra reviews, 10XBNB analysis
- Hostaway: hostaway.com pricing, api.hostaway.com documentation
- Hospitable: hospitable.com pricing, developer.hospitable.com
- Airbnb Partner Program: airbnb.com/software-partners, community.withairbnb.com

### Provider Karşılaştırma Tablosu

| Kriter | Channex | Hostaway | Guesty | Hospitable |
|--------|---------|----------|--------|------------|
| **Fiyat Modeli** | $130/ay + $0.50/unit | $20-40/unit/ay (teklif) | %2.8 revenue + fee | $40/unit/ay (Starter) |
| **Minimum** | Yok | 5+ units | 4+ units | 1 unit |
| **API Erişim** | REST API | OAuth 2.0 + API Key | REST API | Event-driven API |
| **Webhook** | ✅ Global + Property | ✅ Unified | ✅ | ✅ Event-driven |
| **Airbnb Partner** | Channel partner | **Preferred** | **Preferred+** | **Preferred+** |
| **Booking Partner** | 60+ channels | Premier Partner | Yes | Yes |
| **Reservation Ingest** | ✅ Webhook | ✅ API + Webhook | ✅ | ✅ |
| **Availability Sync** | ✅ Push/Pull | ✅ API | ✅ | ✅ |
| **Rate/Pricing Sync** | ✅ | ✅ | ✅ | ✅ (Ayrı module) |
| **Messaging** | Review Hub (ayrı) | Unified Inbox | AI Replies | AI Messaging |
| **Multi-Property** | ✅ API Key per account | ✅ | ✅ | ✅ |
| **Vendor Lock-in** | Düşük (whitelabel) | Orta | Yüksek | Orta |
| **Sandbox** | ✅ Ücretsiz | ❌ Yok | ❌ Yok | ❌ Yok |
| **Dokümantasyon** | docs.channex.io | api.hostaway.com | Geliştirici dostu | developer.hospitable.com |

### Vendor Lock-in Analizi

| Provider | Lock-in Seviyesi | YALIHAN İçin Risk |
|----------|-----------------|-------------------|
| **Channex** | Düşük | ✅ En düşük risk — whitelist odaklı, API-first |
| **Hostaway** | Orta | ⚠️ Derin entegrasyon gerekebilir |
| **Guesty** | Yüksek | ❌ Giriş 1-3 unit minimum, karmaşık onboarding |
| **Hospitable** | Orta | ⚠️ Messaging odaklı, EX-001 için ideal ama pricing ayrı |

### Airbnb Partner Status Etkisi

| Status | Provider | API Erişim | Avantaj |
|--------|----------|------------|---------|
| **Preferred+** | Guesty, Hospitable | Privileged webhooks (sub-minute) | Erken erişim, hızlı destek |
| **Preferred** | Hostaway, Lodgify, Smoobu | Standard API (5-15 min polling) | Güvenilir ama daha yavaş |
| **Channel Partner** | Channex | API üzerinden bağlanır | Airbnb Partner değil |

### SAAB Karar Noktası: Birincil + Yedek Seçimi

**Birincil Tavsiye: Channex**
- ✅ Whitelabel channel manager API (PMS için tasarlanmış)
- ✅ Sandbox ücretsiz — hemen test edilebilir
- ✅ 60+ channel desteği
- ✅ Düşük vendor lock-in
- ✅ Booking.com, Airbnb, Expedia desteği
- ✅ Pricing: $130/ay platform + $0.50/unit (vacation rental)
- ⚠️ Airbnb Preferred değil (channel üzerinden bağlanıyor)
- ⚠️ EX-001 messaging ayrı domain (GuestCommunication)

**Yedek/Alternatif: Hostaway**
- ✅ Airbnb Preferred partner
- ✅ Kapsamlı API dokümantasyonu
- ✅ 26+ direct channels
- ❌ Public pricing yok (teklif gerekli)
- ❌ Vendor lock-in daha yüksek

---

## Discovery Sonuçları

### Keşfedilen Faktlar

1. **Airbnb Direct API: Kapalı** — Partner program bireysel başvuruya kapalı (2026)
2. **Channex: En Uygun** — PMS odaklı, sandbox var, transparent pricing
3. **Hostaway: Güçlü Alternatif** — Airbnb Preferred, kapsamlı ecosystem
4. **Hospitable: EX-001 Messaging İçin İdeal** — AI messaging strongest, ama pricing module ayrı
5. **Guesty: Enterprise Odaklı** — 25+ units gereksinimi, onboarding gerekli

### Önerilen Yol Haritası

```
Faz 1: Channex ile Başla
├── ✅ Sandbox: staging.channex.io (ücretsiz)
├── ✅ API test: 2-4 hafta
└── ✅ Availability sync: Wave 1

Faz 2: Hostaway Ekonomi Araştır
├── ⚠️ Pricing: $20-40/unit/ay teklif al
└── ⚠️ Airbnb Preferred: daha derin entegrasyon

Faz 3: EX-001 Messaging
└── Hospitable değerlendirmesi (GuestCommunication domain)

Faz 4: Gelecek Seçenekler
└── Airbnb Direct (Partner başvurusu — uzun vadeli)
```

---

## Referanslar

- `app/Contracts/ChannelManager/ChannelSyncContract.php`
- `app/Infrastructure/ChannelManager/Adapters/AirbnbChannelAdapter.php`
- `app/Infrastructure/ChannelManager/Adapters/ICalAdapter.php`
- `app/Domain/ChannelManager/Enums/Channel.php`
- `app/Models/IlanTakvimSync.php`
- `docs/sprints/CHANNEL_MANAGER_DISCOVERY.md`
- `docs/sprints/EX-001-WAVE2-AIRBNB_DELIVERY.md`
- `docs/BEKCI_CHANGELOG.md` — CHANNEL_MANAGER Wave 1 CERTIFIED

---

## SAAB Kapanış Kartı

| # | Kriter | Durum |
|---|---------|-------|
| D1 | Channex capability haritası tamamlandı | ✅ |
| D2 | `ChannelSyncContract` uyumluluk analizi yapıldı | ✅ |
| D3 | AirbnbChannelAdapter ↔ ChannelSyncContract gap netleşti | ✅ |
| D4 | Webhook ingest sınırı belirlendi (Wave 2) | ✅ |
| D5 | Pricing sync kapsam dışı kararı verildi | ✅ |
| D6 | Tenant isolation modeli doğrulandı | ✅ |
| D7 | ADR-006 taslağı oluşturuldu | ✅ |
| D8 | Wave 1 implementasyon listesi netleşti | ✅ |
| D9 | Provider karşılaştırması yapıldı | ✅ **YENİ** |
| D10 | Birincil + yedek provider önerisi | ✅ **YENİ** |

**Sonuç:** Discovery tamamlandı. Birincil provider: **Channex**, yedek: **Hostaway**.
