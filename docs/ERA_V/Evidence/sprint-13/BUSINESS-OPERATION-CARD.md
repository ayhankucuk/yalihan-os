# Business Operation Card

**Sprint 13: Channel Manager — Internal Automation Architecture**
**Date:** 2026-07-29

---

## Operation

```
Reservation Availability Synchronization
```

---

## Capability

```
Channel Manager — Availability Synchronization Engine
```

---

## Trigger

```
Canonical reservation confirmed
        OR
Operational block created (maintenance, etc.)
```

---

## Before (Baseline — Manuel)

```
1. Rezervasyonu manuel kontrol et
2. İlgili Property'yi bul
3. Tarih aralığını hesapla
4. Çakışmayı manuel kontrol et (calendar aç)
5. Airbnb ilan paneline git
6. Takvimi manuel blokla / aç
7. Sonucu manuel doğrula
```

**Manuel adım:** 7
**Ortalama süre:** ~12 dk
**İnsan müdahalesi:** %100

---

## After — Current Certified Scope

```
1. Canonical rezervasyon/block oluşturulur
2. YALIHAN availability'yi otomatik günceller (canonical DB)
3. afterCommit job otomatik başlatılır
4. Airbnb uyumlu payload otomatik hazırlanır
5. Sonuç veya hata sınıflandırılıp kaydedilir

Manuel adım: 7 → 1 (rezervasyon oluşturma)
Otomatik adım: 6
Ortalama süre: ~12 dk → ~5 sn (internal chain)
```

---

## Still Manual / Blocked

| Adım | Durum | Sebep |
|------|-------|-------|
| Gerçek Airbnb kanalında değişikliğin gerçekleşmesi | ❌ BLOCKED | Airbnb API erişimi yok |
| Dış sonucu production ortamında doğrulama | ❌ BLOCKED | External API bağlantısı yok |
| Zaman ölçümü (gerçek kullanıcı) | △ PENDING | Sandbox ortamında ölçüm gerekli |

---

## Automation Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Canonical Reservation / Block                                │
│  (IlanReservation, PropertyAvailability write)              │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  AvailabilitySynchronizationService::synchronize()             │
│  ├── Tenant isolation                                        │
│  ├── Idempotency check                                      │
│  ├── PropertyAvailability write (canonical)                  │
│  └── ChannelSyncExecution::create() (immutable)              │
└──────────────────────────┬────────────────────────────────────┘
                           │ afterCommit()
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  SynchronizeAvailabilityJob                                  │
│  └── AirbnbChannelAdapter::pushAvailability()                │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  AirbnbChannelAdapter                                        │
│  ├── IlanTakvimSync lookup (tenant-scoped)                │
│  ├── external_listing_id (NOT property_id)                 │
│  └── AirbnbAvailabilityMapper::mapBatch()                   │
└──────────────────────────┬────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  AirbnbClient                                              │
│  ├── HMAC-SHA256 signed request                            │
│  └── ChannelApiResponse (success / failure taxonomy)         │
└──────────────────────────────────────────────────────────────┘
```

---

## Evidence

| Kanıt | Durum | Referans |
|-------|-------|----------|
| Domain contracts | ✅ | `ChannelAdapter`, `AvailabilitySynchronizer` |
| Canonical availability write | ✅ | `PropertyAvailability` DB write |
| Queue-first execution | ✅ | `afterCommit()` dispatch |
| Idempotency | ✅ | `idempotency_key` in `SynchronizeAvailabilityCommand` |
| Tenant isolation | ✅ | `enforceTenantIsolation()` |
| Conflict detection | ✅ | `AvailabilitySyncAggregate` |
| Airbnb payload mapping | ✅ | `AirbnbAvailabilityMapper` |
| Failure taxonomy | ✅ | 4 exception types |
| Secret sanitization | ✅ | Exception `toLogContext()` |
| Immutable audit trail | ✅ | `ChannelSyncExecution` model |
| △ 4 skipped integration tests | ⚠️ | S13-CD-001 — SQLite FK ordering |
| ✗ Airbnb production connectivity | ❌ | No API credentials |

---

## Test Coverage

| Test Grubu | Durum | Sonuç |
|-----------|-------|-------|
| E02 Synchronization Service | ✅ | 10/10 pass |
| E02 Aggregates | ✅ | 15/15 pass |
| E03 Mapper & Signer | ✅ | 21/21 pass |
| E03 Adapter (DB) | ⚠️ | 4 skipped (SQLite issue) |

**Toplam:** 46 tests · 77 assertions · 0 failures

---

## Certification Debt

| ID | Konu | Severity | Durum |
|----|------|----------|-------|
| S13-CD-001 | Airbnb adapter FK-ordering integration tests (4 skipped) | P1 | OPEN |
| S13-CD-002 | Full adapter with real Airbnb credentials | P2 | OPEN |

---

## BAI Summary

| Metrik | Baseline | Hedef | Certified Scope |
|--------|----------|--------|----------------|
| Manuel adım | 7 | 2 | ✅ 1 (rezervasyon girişi) |
| Ortalama süre | 12 dk | 45 sn | △ ~5 sn (internal) |
| Dış kanal güncellemesi | Manuel | Otomatik | ❌ BLOCKED |
| Conflict detection | Manuel | Otomatik | ✅ VERIFIED |
| Audit trail | Yok | Immutable | ✅ VERIFIED |

---
