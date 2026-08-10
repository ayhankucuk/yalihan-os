# ADR-006: Channel Manager Provider Architecture — OTA Identity vs Transport Provider Separation

**Status:** ACCEPTED  
**Date:** 2026-08-10  
**SAAB Authorization:** CHANNEL_MANAGER_PROVIDER_DISCOVERY APPROVED WITH AMENDMENTS  
**Deciders:** SAAB Board  
**Supersedes:** —

---

## Context

CHANNEL_MANAGER Wave 1, `ChannelSyncContract` + `ICalAdapter` + `RetryPolicy` + `ChannelSyncExecution`
ile foundation'ı kurdu (12874e9). Ancak Airbnb direct API erişiminin bloke olduğu EX-001 pilot'unda
kanıtlandı. Channex gibi bir intermediary provider üzerinden Airbnb/Booking.com entegrasyonu gerekli.

Discovery aşamasında iki mimari risk tespit edildi:

1. **Vendor lock-in**: `AirbnbChannelAdapter`'ın concrete `ChannexClient`'ı doğrudan inject etmesi,
   ileride Airbnb direct API açıldığında adapter'ın da değişmesini zorunlu kılardı.

2. **Channel identity kirlenmesi**: Channex'i bir `Channel` enum değeri olarak eklemek
   (Channel::CHANNEX), OTA kimliği ile transport sağlayıcısını karıştırırdı.

---

## Decision

### 1. OTA Identity ≠ Transport Provider Identity

> **OTA identity and transport provider identity are separate concerns.
> Airbnb and Booking.com are channels; Channex is a replaceable transport provider.**

`Channel` enum değerleri yalnızca hedef OTA'ları temsil eder:
- `Channel::AIRBNB` → Airbnb platformu
- `Channel::BOOKING` → Booking.com platformu
- `Channel::ICAL` → iCal protokolü
- `Channel::VRBO` → VRBO platformu

Channex `Channel` enum'una **eklenmez**. Channex bir transport katmanıdır.

### 2. ChannelTransportContract — Transport Soyutlama Katmanı

`AirbnbChannelAdapter` concrete `ChannexClient`'ı inject etmez. Araya bir transport
contract girer:

```
ChannelSyncContract
        ↓
AirbnbChannelAdapter / BookingChannelAdapter
        ↓
ChannelTransportContract          ← yeni soyutlama
        ↓
ChannexTransport                  ← Channex impl (şimdi)
AirbnbDirectTransport             ← direct impl (gelecekte, mümkün olursa)
```

```php
interface ChannelTransportContract
{
    /**
     * Push availability data to the provider endpoint.
     * Returns a transport-level result (not domain result).
     */
    public function pushAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelTransportResult;

    /**
     * Pull availability data from the provider endpoint.
     */
    public function pullAvailability(
        int    $tenantId,
        string $externalListingId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelTransportResult;

    /**
     * Test transport connection.
     */
    public function testConnection(int $tenantId): ChannelTransportResult;
}
```

### 3. BookingChannelAdapter — Disabled Stub, Production Binding Dışında

`BookingChannelAdapter` Wave 1'de yazılır ama:
- Production `AppServiceProvider` binding'ine girmez
- Her metod `NOT_IMPLEMENTED` döner
- Sınıf dokümantasyonunda açıkça "DISABLED — not production ready" yazısı yer alır
- Test ortamında bile disabled olduğunu kanıtlayan test yazılır

Bu kural, "stub var = capability var" yanlış anlaşılmasını önler.

### 4. Webhook Ingest — Wave 2 Kapsamı

Channex → YALIHAN reservation creation (webhook ingest) bu wave'e dahil değildir.
Reservation creation `ReservationService` zincirini tetikler — ciddi domain mutation.
Ayrı Discovery Charter ile Wave 2'de ele alınacaktır.

### 5. Pricing Sync — Bu Program Dışı

Channex rate/pricing push capability'si bu program kapsamında değildir.
YALIHAN pricing domain (VillaPricingCalculatorService, PropertyPricingService) önce
stabilize edilmeli; ardından pricing sync ayrı capability olarak açılmalıdır.

---

## Mimari Zincir (Kesinleşen)

```
YALIHAN Canonical Chain
    │
    ├── PropertyAvailability (SSOT)
    │         ↑ write: CanonicalAvailabilityService (tek write path)
    │         ↓ read: OperationalCalendarService
    │
    └── ChannelSyncContract (provider-neutral, OTA kimliği)
              │
              ├── AirbnbChannelAdapter
              │         └── ChannelTransportContract
              │                   └── ChannexTransport → Channex API → Airbnb
              │
              ├── BookingChannelAdapter (DISABLED STUB — Wave 2+)
              │         └── ChannelTransportContract (not connected)
              │
              └── ICalAdapter (direct, no Channex)

Channex Layer (transport only)
    ├── ChannexTransport implements ChannelTransportContract
    ├── ChannexClient (HTTP, auth, retry, timeout)
    └── ChannexAvailabilityMapper (canonical format → Channex API format)
```

---

## Uyumluluk Kuralları (SAAB Mandated)

| Kural | Zorunlu |
|-------|---------|
| `Channel` enum Channex içermez | ✅ |
| `AirbnbChannelAdapter` `ChannelTransportContract` inject eder | ✅ |
| `ChannexClient` doğrudan adapter'a inject edilmez | ✅ |
| `BookingChannelAdapter` production binding'e girmez | ✅ |
| `PropertyAvailability`'e doğrudan write yok | ✅ |
| Credential log'a yazılmaz | ✅ |
| Her transport çağrısı `tenant_id` içerir | ✅ |
| Webhook ingest Wave 2 | ✅ |
| Pricing sync bu program dışı | ✅ |

---

## Test Gereksinimleri (Wave 1 SAAB Mandatory)

SAAB'ın Wave 1 test paketinde zorunlu gördüğü senaryolar:

| # | Test | Kapsam |
|---|------|--------|
| T1 | Tenant isolation — yanlış tenant_id adapter'ı bloklıyor | Tenant isolation |
| T2 | Idempotency — aynı correlationId iki kez gönderilirse no-op | Idempotency |
| T3 | Timeout davranışı — transport timeout → retryable failure | Retry |
| T4 | Malformed provider response — adapter exception atmaz | Graceful failure |
| T5 | Channex outage — ChannelSyncResponse.failure retryable=true | Circuit behavior |
| T6 | Adapter domain state yazmıyor — PropertyAvailability satır sayısı değişmiyor | ADR-006 constraint |
| T7 | ChannelSyncContract Channex leak yok — interface Channex'e bağımlı değil | Transport abstraction |
| T8 | Disabled BookingChannelAdapter dış çağrı yapmıyor | NOT_IMPLEMENTED safety |
| T9 | ChannelTransportContract binding AppServiceProvider'da doğru | Container binding |
| T10 | AirbnbChannelAdapter ChannelSyncContract implement ediyor | Contract compliance |

---

## Consequences

### Olumlu
- Yarın Airbnb direct API açılırsa `AirbnbDirectTransport` yazılır; adapter dokunulmaz
- Channex yerine başka intermediary geçilirse sadece `ChannexTransport` değişir
- "Booking destekleniyor" yanılgısı önlendi (disabled stub)
- Wave 1 test paketi transport abstraction'ı doğrular

### Kabul Edilen Borçlar
- CHANNEL_MANAGER_PROVIDER_WAVE2_DEBT-001: Webhook ingest (Channex → YALIHAN reservation)
- CHANNEL_MANAGER_PROVIDER_WAVE3_DEBT-001: Pricing sync (rate push)
- CHANNEL_MANAGER_BOOKING_DEBT-001: BookingChannelAdapter production implementation

---

## Referanslar

- `app/Contracts/ChannelManager/ChannelSyncContract.php`
- `app/Infrastructure/ChannelManager/Adapters/AirbnbChannelAdapter.php`
- `app/Domain/ChannelManager/Enums/Channel.php`
- `docs/sprints/CHANNEL_MANAGER_PROVIDER_DISCOVERY.md`
- `docs/BEKCI_CHANGELOG.md` — CHANNEL_MANAGER Wave 1 CERTIFIED (12874e9)
