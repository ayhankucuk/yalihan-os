# ERA V Phase 2 Roadmap — Autonomous Operations

**Phase:** ERA V Phase 2
**Title:** Autonomous Operations
**Start Date:** 2026-07-29
**Board Resolution:** BR-20260729-ERAV001
**Status:** ACTIVE

---

## Phase 2 Vision

> *"Phase 1, Knowledge Core altyapısını kurdu. Phase 2: bu altyapıyı kullanarak gerçek operasyonların daha büyük bölümünü insan müdahalesi olmadan tamamlamak."*

---

## Phase 2 Sprint Roadmap

| Sprint | Capability | Exit Question | Status |
|--------|------------|--------------|--------|
| **Sprint 13** | Channel Manager | Platform bir kanal için rezervasyon ve uygunluk senkronizasyonunu otomatik yönetebiliyor mu? | ✅ CERTIFIED |
| **Sprint 14** | Property Command Center | Bir property'nin günlük operasyonları tek bir ekrandan yönetilebiliyor mu? | 🚀 LAUNCHED |
| **Sprint 15** | Action Center | Sistem yapılacak işleri otomatik üretip önceliklendiriyor mu? | ⏳ PLANNED |
| **Sprint 16** | Knowledge Core AI | AI, Knowledge Core kullanarak doğrulanabilir operasyon önerileri üretebiliyor mu? | ⏳ PLANNED |

---

## Sprint 13 — Channel Manager ✅ CERTIFIED

**Date:** 2026-07-29
**Duration:** 1 session

### Exit Question
> "Sprint 13 sonunda YALIHAN, en az bir dış kanal için rezervasyon ve uygunluk senkronizasyonunu hiçbir manuel müdahale olmadan otomatik yönetebiliyor mu?"

### Business Operation Automated
```
ÖNCE: Manuel 7 adım, ~12 dk, %100 insan müdahalesi
SONRA: 1 adım (rezervasyon girişi), ~5 sn internal chain
```

### 4-Gate Results
| Gate | Result |
|------|--------|
| G-01 Capability | ✅ PASS |
| G-02 Test | ✅ PASS (46 tests · 77 assertions) |
| G-03 Internal | ✅ PASS |
| G-03 External | ❌ BLOCKED (API yok) |
| G-04 Architecture | ✅ VERIFIED |
| G-04 Production | ❌ BLOCKED (API yok) |

### Epics
- E01: Domain Foundation ✅
- E02: Canonical Synchronization ✅
- E03: Airbnb Adapter Architecture ✅

### Certification Debt
| ID | Konu | Severity |
|----|------|----------|
| S13-CD-001 | 4 skipped integration tests | P1 |
| S13-CD-002 | Airbnb API yok | P2 |
| S13-CD-003 | Production BAI yok | P2 |

### Documentation
- Charter: `docs/ERA_V/Phase_Reports/SPRINT-13-CHARTER.md`
- Certification: `docs/ERA_V/Phase_Reports/SPRINT-13-CERTIFICATION.md`
- Evidence: `docs/ERA_V/Evidence/sprint-13/`

---

## Sprint 14 — Property Command Center 🚀 LAUNCHED

**Date:** 2026-07-30
**Status:** ACTIVE

### Exit Question
> "Bir property'nin günlük operasyonları tek bir ekrandan yönetilebiliyor mu?"

### 4-Gate
| Gate | Soru |
|------|------|
| G-01 | Çalışan capability |
| G-02 | Test kanıtı |
| G-03 | Operasyonel kanıt |
| G-04 | BAI Impact |

### Business Operation Automated
```
ÖNCE: 7 ayrı sayfa/site acma, ~12 dk
SONRA: Tek sayfa, ~45 sn
```

### Epics
| Epic | Hedef |
|------|-------|
| E01 | Property Command Center Aggregate / View Model |
| E02 | Reservation & Availability Panel (Sprint 13 entegrasyonu) |
| E03 | Listing & Publication Status |
| E04 | Timeline & Execution History |
| E05 | Command Actions (Publish, Sync, Reserve vb.) |
| E06 | Certification & Evidence |

### Key Integration Points (Sprint 13)
- `ChannelSyncExecution` model → E04 sync history
- `AvailabilitySyncAggregate` → E02 müsaitlik paneli
- `AirbnbChannelAdapter` → E02 "Senkronize Et" butonu
- `IlanTakvimSync` → E02 kanal eşleşmesi

### Non-Goals
- Yeni domain logic yazmak
- Mobil UI
- Çoklu property karşılaştırma
- Gerçek zamanlı WebSocket updates
- Airbnb/Booking API entegrasyonu

### Documentation
- Charter: `docs/ERA_V/Phase_Reports/SPRINT-14-CHARTER.md`
- Evidence: `docs/ERA_V/Evidence/sprint-14/`

---

## Sprint 15 — Action Center ⏳ PLANNED

**Target:** Q3 2026

### Exit Question (Draft)
> "Sistem yapılacak işleri otomatik üretip önceliklendiriyor mu?"

### Preliminary Scope
- AI-driven task generation from domain events
- Priority scoring based on business rules
- Action assignment and tracking
- Integration with existing notification system

### Dependencies
- Sprint 14 (Property Command Center) must be certified

---

## Sprint 16 — Knowledge Core AI ⏳ PLANNED

**Target:** Q3 2026

### Exit Question (Draft)
> "AI, Knowledge Core kullanarak doğrulanabilir operasyon önerileri üretebiliyor mu?"

### Preliminary Scope
- Knowledge Graph query integration
- AI-powered recommendation engine
- Explainable AI (citation of knowledge sources)
- Human-in-the-loop approval workflow

### Dependencies
- Sprint 15 (Action Center) must be certified
- Knowledge Graph (ERA V Phase 1) must be stable

---

## Phase 2 Success Metrics

| Metric | Sprint 13 | Sprint 14 | Sprint 15 | Sprint 16 |
|--------|-----------|-----------|-----------|-----------|
| Manuel adim | 7 → 1 | 7 → 2 | TBD | TBD |
| Bilgi noktalari | Log'da | 1 sayfa | TBD | TBD |
| Operasyon gorunurluk | Dusuk | Yuksek | TBD | TBD |
| AI oneri | Yok | Yok | TBD | TBD |

---

## Cross-Sprint Dependencies

```
Sprint 13 (Channel Manager)
    │
    ▼
Sprint 14 (Property Command Center)
    │
    ├──▶ Sprint 15 (Action Center)
    │           │
    │           ▼
    │      Sprint 16 (Knowledge Core AI)
    │
    └──▶ Sprint 16 (Knowledge Core AI)
```

---

## Phase 2 Board Resolutions

| Resolution | Date | Subject |
|------------|------|---------|
| BR-20260729-ERAV001 | 2026-07-29 | ERA V Charter Adoption |
| BR-20260730-ERAV002 | 2026-07-30 | Sprint 13 Certification + Sprint 14 Launch |

---

## Known Blockers

| Blocker | Sprint | Resolution |
|---------|--------|------------|
| Airbnb API credentials | 13, 14 | S13-CD-002 — sandbox erisimi gerekli |
| Real external channel access | 13, 14 | Kanal partner anlasmasi gerekli |
