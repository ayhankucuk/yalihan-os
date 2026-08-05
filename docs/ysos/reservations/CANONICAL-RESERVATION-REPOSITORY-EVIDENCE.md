# Canonical Reservation — Repository Kanıtı
**Tarih:** 2026-08-05
**Durum:** Araştırma — Charter Öncesi
**Analist:** SAAB / Claude Opus 4.8

---

## 1. Mevcut Mimari Haritası

### 1.1 Domain Model
```
PropertyReservation (Model)
├── tenant_id          — Tenant isolation (G1 Sprint 22)
├── property_id        — FK → Ilan
├── ilan_id            — Legacy alias (property_id ile paralel)
├── start_date         — Rezervasyon başlangıcı
├── end_date          — Rezervasyon bitişi
├── nights             — Hesaplanan gece sayısı
├── guest_name        — Misafir bilgileri
├── guest_phone/email
├── guest_count
├── notes
├── reservation_state  — Enum: PENDING, CONFIRMED, BLOCKED, CANCELLED
├── finansal_durum     — Money Core Sprint
├── depozito_tutari/durumu
├── locked_nightly_rate
├── booking_currency/fx_rate/country_code
└── Timestamps + soft deletes
```

### 1.2 State Machine
```
ReservationState Enum:
├── PENDING    — Bekliyor
├── CONFIRMED  — Onaylandı (active block)
├── BLOCKED    — Bloke (geçici hold)
└── CANCELLED  — İptal (availability freed)

Transition Rules (implicit):
PENDING  → CONFIRMED  (manual/gateway confirmation)
CONFIRMED → CANCELLED (cancellation flow)
BLOCKED  → CONFIRMED  (hold release)
BLOCKED  → CANCELLED  (expired hold)
```

### 1.3 Availability Entegrasyonu
```
PropertyReservation ←→ PropertyAvailability
                    (Dual-write synchronization)

On createReservation():
1. Overlap check (lockForUpdate) — Aynı property/dates için
2. PropertyAvailability::check  — Dış kaynak blokları kontrolü
3. PropertyAvailability::block   — is_available=false, reservation_id=$id
4. PropertyReservation::create   — CONFIRMED state

On cancelReservation():
1. PropertyReservation::update   — CANCELLED state
2. PropertyAvailability::update  — is_available=true, reservation_id=null
   (Sadece source_system='internal' kayıtları)
```

---

## 2. Mevcut Test Coverage

| Test Dosyası | Test Sayısı | Kapsam |
|--------------|-------------|--------|
| ReservationConcurrencyTest.php | 3 | Double booking prevention, reopen on cancel, min stay |
| ReservationServiceTest.php | 4 | Block integration, external source overlap, cancel with Airbnb |
| **Toplam** | **7** | — |

### 2.1 Coverage Gap Analizi
| Senaryo | Mevcut | Eksik |
|---------|--------|-------|
| Overlap check | ✅ | — |
| External availability block | ✅ | — |
| Airbnb iCal sync | ✅ | — |
| Partial date conflict | ✅ | — |
| Concurrent race condition | ⚠️ | lockForUpdate var, gerçek concurrent test yok |
| State transition validation | ❌ | Implicit, explicit machine yok |
| Domain event emission | ❌ | Availability events var, reservation events yok |
| Idempotency | ❌ | Sadece cancel() idempotent |
| Multi-tenant isolation | ⚠️ | G1 ile eklendi, explicit test yok |

---

## 3. Availability Engine — Mevcut Durum

### 3.1 PropertyAvailability Tablosu
```sql
property_availabilities (çoğul — LP-008 doğrulandı ✅)
├── tenant_id
├── property_id
├── date (unique per property)
├── is_available (boolean)
├── block_reason (string)
├── priority_tier (integer — TIER_RESERVATION, TIER_OWNER_BLOCK, vs.)
├── idempotency_key
├── source_system (internal, airbnb_ical, booking_com, vs.)
├── external_ref
├── reservation_id (FK → PropertyReservation)
├── price (optional override)
├── availability_version
├── origin (RESERVATION, MAINTENANCE, OWNER_BLOCK, EXTERNAL)
├── projection_generated_at
├── projection_source
├── conflict_reason
└── ulke_id
```

### 3.2 CanonicalAvailabilityService
```php
interface PropertyAvailabilityContract
├── checkAvailability()      — Date range availability check
├── blockDateRange()         — Manual block creation
├── unblockDateRange()       — Manual block removal
├── rebuildAvailabilityProjection() — Rebuild from reservations
└── Constants: TIER_* / ORIGIN_* / PROJECTION_SOURCE_*
```

### 3.3 Event Modeli
```
Events:
├── PropertyAvailabilityBlockedEvent
├── PropertyAvailabilityUnblockedEvent
└── PropertyAvailabilityConflictDetectedEvent

Missing (Domain Events):
├── ReservationCreatedEvent      ❌
├── ReservationConfirmedEvent    ❌
├── ReservationCancelledEvent    ❌
└── ReservationConflictDetectedEvent ❌
```

---

## 4. Boşluk Analizi (Gap Analysis)

### 4.1 Canonical Aggregate Gerekli mi?

**Evet — Neden:**
1. **Invariant Koruması:** Aynı mülkte çakışan iki CONFIRMED rezervasyon olmamalı
2. **Event Sourcing:** Domain event'leri eksik
3. **State Machine:** Explicit transitions yok
4. **Transactional Boundary:** Mevcut kod transaction içinde ama aggregate pattern değil

### 4.2 Önerilen Aggregate Sınırı
```
CanonicalReservation (Aggregate Root)
├── ReservationId
├── PropertyId (value object veya FK)
├── GuestInformation (value object)
├── DateRange (value object — start, end, nights)
├── FinancialSnapshot (value object — locked rates, currency)
├── ReservationState (enum — PENDING, CONFIRMED, CANCELLED)
├── TenantId
└── Domain Events[] ( ReservationCreated, ReservationCancelled, vs. )
```

### 4.3 İlişki Haritası
```
CanonicalReservation (1)
    └── PropertyAvailability (many) — blocked dates
    └── Ilan (Property) (1)        — property reference

CanonicalReservation (1)
    └── User (Creator) (1)         — created_by
```

---

## 5. SAAB Soru-Cevap

### Q: YALIHAN çakışan rezervasyonları güvenilir biçimde engelliyor mu?
### A: Evet — Mevcut test coverage bunu doğruluyor.
- Overlap check: `lockForUpdate()` ile race condition koruması
- Availability check: External source blokları da kontrol ediliyor
- Ancak: **Explicit aggregate/invariant testi yok**

### Q: Canonical Aggregate mevcut kodu bozar mı?
### A: **Hayır — Paralel implementasyon önerilir.**
1. Mevcut `ReservationService` korunmalı (backward compatibility)
2. Yeni `CanonicalReservation` service/aggregate eklenmeli
3. Feature flag ile geçiş yapılmalı
4. Event-driven communication ile senkronizasyon

### Q: Canonical Reservation ne kazandırır?
### A:
| Kazanım | Açıklama |
|---------|----------|
| Event Sourcing | Audit trail, replay, projection |
| Explicit Invariants | State machine ile korunan business rules |
| CQRS Read Model | Availability projection ayrı read model |
| Channel Manager Temeli | Canonical event'ler = multi-channel sync |
| Conflict Resolution | Priority tier ile dış kaynak çakışmaları |

---

## 6. Sonraki Adım: Charter Taslağı

**Charter Gerekli İçerik:**
1. Aggregate sınırı tanımı
2. Invariant'lar (business rules)
3. Event modeli (Domain events)
4. Mevcut kod ile uyumluluk stratejisi
5. Test senaryoları (happy path + edge cases)
6. Migration planı (feature flag based)

**Charter Onay Sonrası:**
- Implementation: Claude Sonnet 4.6
- Test-first approach
- Feature flag: `canonical_reservation_enabled`

---

## 7. Evidence Links

- Model: `app/Models/PropertyReservation.php`
- Service: `app/Services/ReservationService.php`
- Enum: `app/Enums/ReservationState.php`
- Tests: `tests/Feature/ReservationConcurrencyTest.php`, `tests/Feature/ReservationServiceTest.php`
- Availability: `app/Models/PropertyAvailability.php`, `app/Services/Property/CanonicalAvailabilityService.php`
- LP-008: `memory/LEARNED_PATTERNS.md` — Tablo adı doğrulandı ✅

---

**SAAB Onay Bekleniyor:** Charter taslağı için devam edilecek.
