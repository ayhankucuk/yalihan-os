# EX-001 WAVE 2 — Airbnb Delivery + Live Pilot

> **Mission:** EX-001 — Guest Communication Agent
> **Wave:** WAVE 2 — Airbnb Delivery
> **Status:** ▶ IN PROGRESS
> **Date:** 2026-08-07

---

## WAVE 2 Görevi

WAVE 1'de oluşturulan altyapıyı gerçek Airbnb teslimatıyla tamamlamak.

```
SendGuestWelcomeMessageJob
        ↓
Airbnb Delivery Adapter
        ↓
Delivery Result
        ↓
Delivery Audit
        ↓
First Real Reservation
```

---

## WAVE 2 Yapar

* Airbnb gönderim sözleşmesi ve adapter
* Credential/tenant çözümleme
* Timeout, retry ve idempotency
* Harici mesaj referansını kaydetme
* Başarılı/başarısız teslim audit'i
* Canlı veya kontrollü pilot rezervasyon testi

---

## WAVE 2 Yapmaz

* Check-in/Wi-Fi/yol tarifi akışı (→ WAVE 3)
* Mid-stay mesajları (→ WAVE 3)
* Check-out ve review talebi (→ WAVE 4)
* Fiyat, iptal veya rezervasyon kararı
* Genel Airbnb Channel Manager refactor'u

---

## Başarı Sorusu

> Gerçek bir rezervasyon onaylandığında YALIHAN, doğru dildeki karşılama mesajını 60 saniye içinde Airbnb kanalına tenant-safe ve idempotent biçimde teslim edip sonucu denetlenebilir olarak kaydedebiliyor mu?

---

## Pilot Güvenlik Kriterleri

| # | Kriter | Açıklama |
|---|--------|----------|
| 1 | Pilot mode flag | Yalnızca test property/tenant üzerinde çalışır |
| 2 | Idempotency | Aynı rezervasyona duplicate mesaj gönderilmez |
| 3 | Pre-flight audit | Mesaj metni gönderim öncesi kaydedilir |
| 4 | Failure handling | Başarısızlık sessizce yutulmaz, loglanır |
| 5 | Kill switch | Feature flag ile otomasyon durdurulabilir |
| 6 | Tenant isolation | Yanlış tenant'a gönderim engellenir |

---

## Mimari

### Airbnb Adapter Interface

```
AirbnbChannelAdapter
├── sendWelcomeMessage(GuestWelcomeNotification): DeliveryResult
├── resolveCredentials(tenantId): AirbnbCredentials
├── createIdempotencyKey(reservationId, type): string
└── mapDeliveryResult(response): DeliveryResult
```

### Delivery Result

```php
enum DeliveryStatus {
    SENT,
    FAILED,
    DUPLICATE,
    RATE_LIMITED,
    INVALID_CREDENTIALS,
}

class DeliveryResult {
    public function __construct(
        public readonly DeliveryStatus $status,
        public readonly ?string $externalId = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $retryable = false,
    ) {}
}
```

---

## Test Senaryoları

| # | Senaryo | Beklenen Sonuç |
|---|--------|-------------------|
| 1 | Geçerli rezervasyon, geçerli credential | SENT + external ID |
| 2 | Geçersiz credential | INVALID_CREDENTIALS + alert |
| 3 | Rate limit | RATE_LIMITED + retry |
| 4 | Duplicate gönderim | DUPLICATE + no-op |
| 5 | Timeout | FAILED + retry |
| 6 | Başarısız + max retry | FAILED + alert + audit |

---

## Deliverables

| # | Deliverable | Hedef |
|---|------------|-------|
| 1 | AirbnbAdapterContract | Interface tanımı |
| 2 | AirbnbAdapter | Gerçek Airbnb API implementasyonu |
| 3 | DeliveryResult enum | Durum yönetimi |
| 4 | Idempotency key | Duplicate önleme |
| 5 | Credential service | Tenant/API key çözümleme |
| 6 | Pilot feature flag | Kill switch |
| 7 | Delivery audit | Audit log |
| 8 | Canlı pilot test | İlk gerçek misafir |

---

## Exit Criteria

| # | Kriter | Kanıt |
|---|--------|-------|
| 1 | Airbnb API başarılı teslim | External message ID |
| 2 | Idempotency çalışıyor | Duplicate test |
| 3 | Tenant isolation korunuyor | Cross-tenant test |
| 4 | Failure handling çalışıyor | Retry + alert test |
| 5 | Audit kaydı oluşuyor | Audit log |
| 6 | Feature flag çalışıyor | Kill switch test |
| 7 | İlk canlı misafir testi | Real reservation evidence |

---

## Status

| Bileşen | Durum |
|---------|--------|
| AirbnbAdapterContract | ⏳ |
| AirbnbAdapter | ⏳ |
| DeliveryResult | ⏳ |
| Idempotency | ⏳ |
| CredentialService | ⏳ |
| PilotFeatureFlag | ⏳ |
| DeliveryAudit | ⏳ |
| Canlı Pilot | ⏳ |

---

**WAVE 2 Status: ▶ IN PROGRESS**
