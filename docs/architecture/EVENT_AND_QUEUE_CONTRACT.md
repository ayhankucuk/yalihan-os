# YALIHAN OS — EVENT AND QUEUE CONTRACT

**Tarih:** 2026-09-07
**Durum:** DOCUMENTED / VALIDATION_PENDING
**Kaynak:** ARCHITECTURE_BACKBONE_AUDIT.md §5, Constitution Madde 12-13, SAB.md Rule 9-10, REGISTRY.md §3

---

## 1. Amaç

Bu belge, Yalıhan OS'de domain event'lerinin ve queue job'larının:
- Naming convention'larını
- Payload sözleşmelerini
- Tenant context gereksinimlerini
- Idempotency kurallarını
- DLQ ve replay politikalarını
- Queue tenant context sözleşmesini

tanımlar.

---

## 2. Event Sözleşmesi

### 2.1 Naming Convention (Constitution Madde 13.2)

```
Kural: Geçmiş zaman kipi
Format: {Aggregate}{ActionPastTense}

Örnekler:
  ✅ ListingPublished
  ✅ MediaUploaded
  ✅ PriceChanged
  ✅ ReservationBooked
  ✅ PropertyCreated

  ❌ PublishListing (gelecek zaman)
  ❌ OnListingPublish (ön ek)
  ❌ ListingPublishEvent (Event suffix)
```

### 2.2 Event Payload Sözleşmesi (Constitution Madde 13.2)

Her event şu alanları taşımalıdır:

```php
interface DomainEvent {
    public function getEventId(): string;      // UUID v4
    public function getOccurredAt(): Carbon;    // Timestamp
    public function getTenantId(): ?int;        // Tenant ID (null = global)
    public function getAggregateId(): int|string; // Aggregate kök ID
    public function getPayload(): DTO;         // Immutable DTO
    public function getIdempotencyKey(): string; // Unique key
    public function getVersion(): int;          // Payload şema versiyonu
}
```

### 2.3 Event Catalog (REGISTRY.md §3 + Genişletme)

| Event | Fırlatan Domain | Dinleyen Modüller | Payload DTO | tenantId | Kanıt |
|-------|-----------------|-------------------|-------------|----------|-------|
| `PropertyCreated` | Property | CRM, Audit | PropertyCreatedDTO | ✅ | REPO_VERIFIED |
| `ListingPublished` | Listing | AI, Portallar, Audit | ListingPublishedDTO | ✅ | REPO_VERIFIED |
| `ListingPriceChanged` | Listing | CRM, Finance | PriceChangedDTO | ✅ | REPO_VERIFIED |
| `MediaUploaded` | Media | Hermes, Listing | MediaUploadedDTO | ✅ | REPO_VERIFIED |
| `MediaVariantsReady` | Media | Listing, Portallar | MediaVariantsReadyDTO | ✅ | REPO_VERIFIED |
| `ReservationBooked` | Reservation | Operations, Finance, CRM | ReservationBookedDTO | ✅ | REPO_VERIFIED |
| `HermesEventLog` | Automation | Audit | HermesEventDTO | 🔴 Leakage | REPO_VERIFIED |

> **Gap:** REGISTRY.md sadece 6 event listeliyor. Sistemde daha fazla event olduğu INFERRED. Tam envanter çıkarılmalı.

### 2.4 Event Versioning

```
Kural: Her event payload version taşır
Format: integer (1, 2, 3...)

Migration:
  v1 → v2: Yeni alan ekleme (optional, backward compatible)
  v2 → v3: Alan tipi değişikliği (breaking, ADR gerekli)

Listener:
  if ($event->getVersion() === 1) {
    // v1 payload işle
  } else {
    // v2+ payload işle
  }
```

### 2.5 Event Idempotency (SAB.md Rule 10, Constitution Madde 12.2)

```
Kural: Her event idempotency_key taşır
Aynı idempotency_key ile event tekrar işlenmez

Implementation:
  - idempotency_key = hash(eventId + aggregateId + occurredAt)
  - İşleme öncesi idempotency_key kontrolü
  - Aynı key varsa skip + log
```

### 2.6 Event Listener İzolasyonu (Constitution Madde 13.2)

```
Kural: Bir listener'ın çökmesi diğerlerini durdurmaz
Mekanizma: Queueable Listeners

Implementation:
  - Her listener kendi queue'da çalışır
  - Listener exception → DLQ
  - Ana işlem etkilenmez
```

---

## 3. Queue Sözleşmesi

### 3.1 Queue Tenant Context

```
Sözleşme:
  1. Job dispatch sırasında tenant context serialize edilir
  2. Job handle sırasında tenant context restore edilir
  3. Queue worker retry'leri tenant context korur
  4. DLQ replay tenant context korur

Interface:
  interface TenantAwareJobInterface {
      public function getTenantId(): ?int;
      public function setTenantId(int $tenantId): void;
  }

Trait:
  trait RestoreTenantContext {
      public function restoreTenantContext(): void {
          if ($this->tenantId) {
              app(TenantContext::class)->set($this->tenantId);
          }
      }
  }
```

### 3.2 Mevcut Job'lar — Tenant Context Durumu

| Job | TenantAware | Kanıt |
|-----|-------------|-------|
| `DailySnapshotsJob` | 🔴 Yok | REPO_VERIFIED |
| `OwnerReportExportJob` | 🔴 Yok | REPO_VERIFIED |
| `NotifyN8nAboutIlanPriceChange` | 🔴 Yok | REPO_VERIFIED |
| `TalepTopluAnalizJob` | 🔴 Yok | REPO_VERIFIED |
| `MediaProcessingJob` | 🔴 Yok | REPO_VERIFIED |
| `AvailabilitySynchronizationService` | 🔴 Yok | REPO_VERIFIED |

> **0 adoption:** Hiçbir job TenantAwareJobInterface kullanmıyor.

### 3.3 DLQ ve Replay (SAB.md Rule 9)

```
Kural: DLQ zorunludur, replay doğrulanmış olmalıdır

Mekanizma:
  - Failed job → DLQ queue
  - DLQ replay: tenant context korumalı
  - Replay idempotent: aynı sonuç
  - Replay doğrulama: test ile

Komutlar:
  php artisan projection:dlq:replay
  php artisan queue:failed
  php artisan queue:retry
```

### 3.4 Worker Restart (SAB.md §6)

```
Kural: Worker restart idempotent olmalı
Mekanizma: Job idempotency_key ile deduplication
```

---

## 4. Hermes Orchestration (Constitution Madde 12)

### 4.1 Hermes Rolü

```
Hermes = Pure Orchestrator
  ✅ Job dispatch
  ✅ Retry politikası
  ✅ Timeout denetimi
  ✅ Rate limiting
  ✅ Cron scheduling
  ✅ Idempotency kontrolü

  ❌ İş mantığı (Constitution Madde 12.3)
  ❌ Fiyat hesaplama
  ❌ İlan yayınlama kararı
  ❌ Müşteri eşleştirme
```

### 4.2 Hermes Event Log

| Alan | Durum | Kanıt |
|------|-------|-------|
| `tenant_id` | 🔴 Leakage tespit edildi | REPO_VERIFIED (SECURITY_EVIDENCE §3) |
| `idempotency_key` | DOCUMENTED | Constitution Madde 12.2 |
| `event_type` | ✅ | REPO_VERIFIED |
| `payload` | ✅ | REPO_VERIFIED |

**Gereken:** Hermes event log'a tenant_id ekleme ve leakage düzeltme.

---

## 5. Kabul Kriterleri

### 5.1 Event

- [ ] Tüm event'ler geçmiş zaman kipiyle isimlendirilmiş
- [ ] Tüm event'ler eventId, occurredAt, tenantId, aggregateId, payload taşır
- [ ] Tüm event'ler idempotency_key taşır
- [ ] Tüm event'ler version taşır
- [ ] Event catalog tam (REGISTRY.md güncel)
- [ ] Listener'lar queueable ve izole

### 5.2 Queue

- [ ] Tüm job'lar TenantAwareJobInterface implemente eder
- [ ] Job dispatch tenant context serialize eder
- [ ] Job handle tenant context restore eder
- [ ] DLQ replay tenant context korur
- [ ] Worker restart idempotent

### 5.3 Hermes

- [ ] Hermes pure orchestrator (iş mantığı yok)
- [ ] Hermes event log tenant_id taşır
- [ ] Hermes event log idempotency_key taşır

---

## 6. Test Gereksinimleri

| Test | Açıklama | Öncelik |
|------|---------|---------|
| Event contract test | Her event payload sözleşmeye uyuyor | P0 |
| Idempotency test | Aynı event iki kez işlendiğinde aynı sonuç | P0 |
| Queue tenant context test | Job tenant boundary'yi aşamaz | P0 |
| DLQ replay test | Replay tenant context korur | P1 |
| Listener isolation test | Bir listener çökerse diğerleri çalışır | P1 |
| Hermes boundary test | Hermes'te iş mantığı yok | P1 |

---

*Bu belge ARCHITECTURE_BACKBONE_AUDIT.md §5 (Karar #5, #6) gereği üretilmiştir. Veri kaynakları: Constitution Madde 12-13, SAB.md Rule 9-10, REGISTRY.md §3, SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md.*
