# RESERVATION_CORE Phase 4 — Availability Projection Charter

**Charter Tarihi:** 2026-08-06
**Hazırlayan:** WenOX (Discovery)
**Sprint Tipi:** Canonical Availability Capability
**Önkoşul:** ✅ RESERVATION_CORE Phase 3 CLOSED (2026-08-06)

---

## Misyon

YALIHAN'da PropertyAvailability tablosu, tüm kanallar için tek doğruluk kaynağı (Single Source of Truth) haline gelir.

---

## Başarı Sorusu

> PropertyAvailability, mevcut rezervasyonları, owner bloklarını, maintenance bloklarını ve external channel bloklarını birleştirerek herhangi bir property ve tarih için "canonical availability" durumunu üretebiliyor mu?

---

## Mevcut Durum (Discovery Findings)

### Phase 2'den Miras Alınan

| Bileşen | Durum | Açıklama |
|----------|--------|-----------|
| `AvailabilityProjectionService` | ✅ Var | Reservation → Availability projection |
| `AvailabilityReplayService` | ✅ Var | Rebuild from canonical |
| `AvailabilityDriftDetector` | ✅ Var | Read-only drift detection |
| `PropertyAvailability` model | ✅ Var | Günlük availability tablosu |

### Phase 3'ten Miras Alınan

| Bileşen | Durum | Açıklama |
|----------|--------|-----------|
| `ConflictDetectionService` | ✅ Var | Priority-aware conflict detection |
| `Priority Matrix` | ✅ Var | 5-tier priority system |
| `Tenant Isolation` | ✅ Var | Cross-tenant koruması |

### Eksik Olanlar (Gap Analysis)

| Gap | Öncelik | Açıklama |
|-----|----------|-----------|
| G1: Timeline/Event Sourcing | P1 | Availability değişikliklerinin immutable event zinciri |
| G2: External Channel Aggregation | P1 | Channel Manager'dan gelen blokların priority ile birleştirilmesi |
| G3: Availability Query API | P2 | Tek endpoint ile canonical availability sorgusu |
| G4: Availability Versioning | P3 | Audit trail için version/timestamp tracking |

---

## Phase 4 Scope

### In Scope (P0)

| Task | Açıklama |
|------|----------|
| P0.1 | `AvailabilityTimelineService` — Immutable event log for availability changes |
| P0.2 | `AvailabilityQueryService` — Unified canonical availability query API |
| P0.3 | `ExternalBlockAggregationService` — Channel blocks with priority resolution |
| P0.4 | Tenant isolation enforcement |

### In Scope (P1)

| Task | Açıklama |
|------|----------|
| P1.1 | Drift detection enhanced coverage |
| P1.2 | Availability projection idempotency guarantee |
| P1.3 | Performance optimization (index, caching) |

### Out of Scope

| Task | Neden |
|------|--------|
| Channel Manager implementation | Ayrı capability |
| Operational Calendar UI | Ayrı capability |
| Automatic drift remediation | Phase 2 E05: read-only |

---

## Mimari Tasarım

### Canonical Availability Query

```
Request: GET /availability/{propertyId}?date=2026-08-15
        │
        ▼
AvailabilityQueryService
        │
        ├── PropertyReservation (CONFIRMED only)
        ├── PropertyAvailability (owner/maintenance blocks)
        ├── ExternalChannelBlocks (Airbnb/Booking/ical)
        │
        ▼
PriorityResolution
        │
        ▼
CanonicalAvailability {
  date: "2026-08-15",
  is_available: bool,
  blocking_sources: [...],
  priority_tier: int,
  reservation_id: int|null
}
```

### Event Timeline

```
AvailabilityChangedEvent {
  property_id,
  date,
  previous_state,
  new_state,
  source,           // reservation|owner|maintenance|external
  actor,            // system|user|channel
  timestamp,
  correlation_id
}
```

---

## Priority Matrix (Phase 3'ten Devam)

| Priority | Source | Value |
|-----------|--------|-------|
| 1 | Maintenance | 1 (highest) |
| 2 | Owner Block | 2 |
| 3 | Confirmed Reservation | 3 |
| 4 | External Channel | 4 |
| 5 | Pending Hold | 5 |

---

## Test Stratejisi

### Zorunlu Testler (10)

| # | Test | Açıklama |
|---|------|-----------|
| T1 | canonical_availability_merges_reservations_and_blocks | CONFIRMED + owner block → correct priority wins |
| T2 | canonical_availability_excludes_terminal_states | COMPLETED/CANCELLED rezervasyonlar availability üretmez |
| T3 | external_channel_block_integrated | External block canonical query'ye dahil |
| T4 | priority_resolution_correct | Higher priority always blocks lower |
| T5 | tenant_isolation_enforced | Cross-tenant availability invisible |
| T6 | timeline_event_created_on_change | Her değişiklik immutable event üretir |
| T7 | timeline_is_immutable | Event kayıtları değiştirilemez |
| T8 | availability_query_is_deterministic | Aynı sorgu her zaman aynı sonucu döner |
| T9 | rebuild_preserves_non_reservation_blocks | Rebuild owner/maintenance blocks korur |
| T10 | drift_detected_when_mismatch | Canonical vs actual drift tespit edilir |

---

## Artisan Komut Önerisi

```bash
# Canonical availability check
php artisan availability:canonical {propertyId} {date}

# Availability timeline
php artisan availability:timeline {propertyId} {startDate} {endDate}

# Channel block aggregation
php artisan availability:aggregate {propertyId} {startDate} {endDate}
```

---

## Başarı Kriterleri

| Kriter | Hedef |
|---------|--------|
| Canonical query response time | < 50ms |
| Timeline event coverage | 100% |
| Priority resolution accuracy | 100% |
| Tenant isolation violations | 0 |
| Drift detection accuracy | 100% |

---

## Sonraki Adımlar

```
Phase 4 Availability Projection
        ↓
Phase 5 Operational Calendar
        ↓
Phase 6 Channel Manager
```

---

## Kapanış Kriteri

| Kriter | Hedef |
|---------|--------|
| AvailabilityQueryService | Tüm kaynakları birleştirir |
| Timeline event sourcing | Immutable audit trail |
| Priority resolution | Phase 3 matrix ile uyumlu |
| Test coverage | 10/10 mandatory PASS |
| Performance | < 50ms response time |

---

*SAAB onayı bekleniyor.*
