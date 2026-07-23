# Sprint 16 Charter — Property Core Capabilities

**Status:** 🔲 PLANNING
**Milestone:** M2 Property Runtime (Sprint 15 — 🟢 CERTIFIED)
**Board Resolution:** BR-20260723-SPRINT16
**Date:** 2026-07-23
**Author:** Chief AI + Agent (Kilo)

---

## Previous Sprint Summary

| Sprint | Milestone | Status | Evidence |
|--------|-----------|--------|----------|
| Sprint 10 | EIOS Registry | 🟢 CERTIFIED | `vS10-certified` |
| Sprint 11 | EIOS Property | 🟢 CERTIFIED | `vS11-certified` |
| Sprint 12 | Property Publish | 🟢 COMPLETE | 31/31 tests |
| Sprint 13 | Replay & Recovery | 🟢 CERTIFIED | `vS13-certified` |
| Sprint 14 | Runtime Metrics & BAI | 🟢 CERTIFIED | 11/11 tests |
| Sprint 15 | Operations Console + M2 | 🟢 CERTIFIED | `vM2-certified` |

---

## Strategic Context

### Property Intelligence Operating System Roadmap

```
M2 ✅ Property Runtime (Tamamlandı — 2026-07-16)

        ↓

Sprint 16 — Property Core Capabilities
        ├── Property Command Center
        ├── Commercial Offering Engine
        └── BAI-first capability

        ↓

Sprint 17 — Reservation Engine

        ↓

Sprint 18 — Channel Manager

        ↓

Sprint 19 — Finance Layer

        ↓

Sprint 20 — Company Brain

        ↓

Sprint 21 — Ask YALIHAN
```

### Architectural Shift

**Eski (Listing-merkezli):**
```
Listing (İlan)
    ├── Fotoğraflar
    ├── Açıklama
    ├── Fiyat
    └── Platformlar
```
Problem: Aynı mülk için birden fazla kayıt oluşabilir. Platform = kayıt bazlı.

**Yeni (Property-merkezli):**
```
Property (Fiziksel varlık)
    ├── Owner
    ├── Commercial Offering    ← Tek mülk → birden fazla sunum
    ├── Listings             ← Tek mülk → birden fazla platform yayını
    ├── Reservations
    ├── Finance
    ├── Documents
    ├── Media
    ├── Operations
    ├── Timeline
    └── AI Intelligence
```
Çözüm: Tek Property, birden fazla Commercial Offering, birden fazla Listing.

---

## Sprint 16 Scope

### Program A — Property Command Center (P0)

**Soru:** Operatör tek bir Property'nin tüm durumunu tek ekrandan görebiliyor mu?

**Deliverables:**
- Property detail dashboard (blade + controller)
- Property summary: durum, metrikler, son işlemler
- Active executions panel (OperationsConsoleController ile entegre)
- Property timeline: state geçişleri, replay zincirleri

**Kalite Kapıları:**
- [ ] Feature test: property detail page renders
- [ ] Property metrikleri API'den geliyor
- [ ] Execution data tenant-scoped

### Program B — Commercial Offering Engine (P0)

**Soru:** Aynı Property için farklı Commercial Offering'ler (satılık / günlük kiralık / sezonluk) yönetilebiliyor mu?

**Deliverables:**
- CommercialOffering model + migration
- Property → CommercialOffering relation
- CommercialOfferingController (CRUD)
- Listing → CommercialOffering relation

**Kalite Kapıları:**
- [ ] Feature test: create/update/list commercial offerings
- [ ] Property'ye bağlı offering'ler listeleniyor
- [ ] Tenant isolation enforced

### Program C — BAI First Capability (P1)

**Soru:** Yeni özellik eklenirken BAI artışı ölçülebilir mi?

**Deliverables:**
- BAI metrikleri dokümantasyonu (ExecutionMetricsService)
- Manual minutes tracking baseline
- Her capability için kazanım kartı

**Kalite Kapıları:**
- [ ] BAI formula dokümantasyonu tamam
- [ ] Baseline metrikler kaydedildi

---

## Board Questions

1. Operatör Property Command Center'dan tüm runtime sorunlarını görebiliyor mu?
2. Commercial Offering Engine ile tek mülk birden fazla sunum yönetebiliyor mu?
3. Yeni özelliklerin BAI etkisi ölçülebilir mi?

---

## Definition of Done

```
Property Command Center
        ↓
Commercial Offering Engine
        ↓
Tests (green)
        ↓
Evidence
        ↓
Sprint Review
```

---

## Sprint Review Standard

> *"Bu sprint sonunda YALIHAN, bir danışmanın gerçek işini daha az manuel adımla tamamlamasını sağlayan yeni bir otomasyon capability'si üretmiş mi?"*

---

## Key Files (Sprint 15 Reference)

| Dosya | Açıklama |
|-------|-----------|
| `app/Models/WorkforceExecution.php` | Execution model |
| `app/Services/Execution/ExecutionMetricsService.php` | BAI metrics |
| `app/Http/Controllers/Admin/OperationsConsoleController.php` | Console API |
| `tests/Feature/Execution/M2ProductValidationTest.php` | M2 validation tests |

---

*Sprint 16 — Property Core Capabilities — PLANNING*
