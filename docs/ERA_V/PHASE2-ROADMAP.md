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

### Certification Preparation Backlog

The Hermes deep audit (`audits/HERMES_DEEP_AUDIT_REPORT.md`, `REPO_VERIFIED`) identified runtime and coverage gaps that must be resolved or explicitly waived before workforce-dependent Sprint 14 certification:

- [ ] Fix `PropertyScoreAgent` PSR-4 namespace/directory mismatch.
- [ ] Fix `DriveAgent` constructor/`HermesServiceProvider` dependency mismatch.
- [ ] Align `NotificationAgent` subscription with the `publishing.decision_ready` event.
- [ ] Remove or explicitly retire the unregistered `PortfolioAgent` dead code.
- [ ] Add unit tests for `PhotoAgent`, `DescriptionAgent`, `PropertyScoreAgent`, `PublishDecisionAgent`, and `NotificationAgent`.
- [ ] Add one end-to-end Workforce chain integration test: Drive → Photo → Description → PropertyScore → PublishDecision → Notification.
- [ ] Record the 10 Hermes technical-debt items and assign severity/owner before certification sign-off.

### Current Sprint 14 Certification Worklist — 2026-08-28

- [x] Add `AdvisorCommandCenter` JSON contract coverage: 6 tests / 45 assertions PASS.
- [x] Verify PropertyHub Dashboard & Hardening tests: 6 tests / 22 assertions PASS.
- [x] Record broader PropertyHub and AI suite results: 35+ PropertyHub tests PASS; 121 AI tests / 570 assertions PASS, with 8 pre-existing failures documented.
- [ ] Run fresh authenticated browser/API verification for PropertyHub and explain any remaining HTTP 500.
- [ ] Run authenticated AdvisorCommandCenter browser flow for `/admin/advisor/command-center` and `/fetch`.
- [ ] Complete G-04 operator timing evidence and update `G-04-BAI-EVIDENCE.md` and `SPRINT-14-CERTIFICATION.md`.
- [ ] Decide Sprint 14 certification outcome: `CERTIFIED` or board-approved `CONDITIONAL PASS`.

**Current boundary:** Sprint 14 remains uncertified until the three open items above have evidence. The Hermes runtime fixes and missing chain tests are the next reliability work after Sprint 14 evidence closure.

## Prioritized Next Work — 2026-08-28

### Priority 1 — Close Sprint 14 Certification Blockers

- [ ] Fresh authenticated browser/API verification for PropertyHub; capture the current HTTP 500 exception if it persists.
- [ ] Authenticated AdvisorCommandCenter flow verification for `/admin/advisor/command-center` and `/fetch`.
- [ ] Complete G-04 operator timing measurement and update the BAI evidence and certification artifact.
- [ ] Issue the Sprint 14 certification decision: `CERTIFIED` or board-approved `CONDITIONAL PASS`.

### Priority 2 — Hermes Workforce Reliability

- [ ] Resolve the PropertyScoreAgent PSR-4 namespace/directory mismatch.
- [ ] Resolve the DriveAgent constructor and service-provider dependency mismatch.
- [ ] Align the NotificationAgent subscription with the publishing decision event.
- [ ] Add the missing Workforce unit tests and one full chain integration test.

### Priority 3 — AI Suite Pre-Existing Failures

- [ ] Fix the six `DescriptionReviewModalTest` fixture/SQLite uniqueness failures.
- [ ] Fix the two `FeatureFeedbackContractTest` authorization/permission setup failures.
- [ ] Re-run the complete AI suite and target 129/129 PASS, documenting any approved exclusions.

### Priority 4 — Location and Migration Risk Research

- [ ] Compare production MySQL and local SQLite behavior for location reconciliation.
- [ ] Verify orphan FK impact across `iller`, `ilceler`, `mahalleler`, and `ilanlar`.
- [ ] Review the backward compatibility of `2026_08_26_000002_fix_bina_yasi_column_type.php`.
- [ ] Produce a no-data-loss migration/reconciliation plan before any production execution.

### Priority 5 — Sprint 15/16 Architecture Prerequisites

- [ ] Map domain events from listing publication, reservation, and CRM modules into Action Center work items.
- [ ] Define Action Center task priority, assignment, lifecycle, and evidence contracts.
- [ ] Define Knowledge Core AI provenance/explainability contract and supporting data models.

### Priority 6 — Category/Publication-Type Feature Matrix

The resolver matrix review found that only selected Konut/Villa combinations have rich assignments; several categories currently fall back to five global features with zero required fields. Before any seeder or production data mutation:

- [ ] Reconcile `CategoryFieldSchemaSeeder` definitions with the canonical `features` and `feature_assignments` model.
- [ ] Define and contract-test Arsa & Arazi fields: imar/tapu, ada, parsel, pafta, KAKS, TAKS, gabari, road frontage, and infrastructure switches.
- [ ] Define and contract-test rental fields for Konut and İşyeri: deposit, advance rent, usage status, and related financial fields.
- [ ] Define and contract-test İşyeri fields: usage area, open/closed area, ceiling height, loading ramp, and electrical power.
- [ ] Define and contract-test Yazlık Kiralama fields: minimum stay, check-in/out times, cleaning fee, damage deposit, and pool maintenance.
- [ ] Define and contract-test Turistik Tesis fields: accommodation capacity and domain-specific operational/licensing fields.
- [ ] Define and contract-test Projeden Satış fields and publication-type mappings.
- [ ] Build a complete category × subtype × publication-type matrix report, including resolved count, required count, scope, and fallback reason.
- [ ] Add negative tests for missing assignments and verify that global fallback is explicit rather than silently presented as a complete template.
- [ ] Only after review and explicit authorization, prepare `CategoryFeatureMatrixSeeder`; do not run it against production without data-contract, tenant, rollback, and approval evidence.

## New Research Findings — Property Engine and Operational Flows

**Evidence status:** `REPO_VERIFIED` research findings; implementation and production behavior require separate tests and live evidence.

### P0 — Template and Assignment Completeness

- [ ] Reconcile `FeatureTemplateResolver` fallback behavior with the publication gate; prevent missing category templates from appearing complete through silent global fallback.
- [ ] Define the canonical Arsa, İşyeri, and Kiralık feature sets and assignments.
- [ ] Prepare a `FeatureAssignmentSeeder`/matrix change only after contract, tenant, rollback, and explicit data-change approval.

### P1 — Sidebar and Property Engine Navigation

- [ ] Repair or remove the six missing/incorrect sidebar routes.
- [ ] Consolidate Property Hub, templates, categories, features, packs, AI schema suggestions, and dependency rules under one Property Engine menu.
- [ ] Resolve legacy field-dependency versus `FeatureTemplateResolver` navigation/source-of-truth ambiguity.
- [ ] Add route-audit and authenticated navigation tests for the consolidated menu.

### P2 — Channel and iCal Reliability

- [ ] Verify the 15-minute calendar sync schedule and job execution evidence.
- [ ] Test UTC → `Europe/Istanbul` date-boundary normalization and double-booking protection.
- [ ] Confirm circuit-breaker behavior for live TKGM/channel requests without exposing calendar secrets.

### P3 — Lead Matching Integration

- [ ] Trace the `IlanPublished` event through the matching job and notification chain.
- [ ] Add an integration test proving that a published listing creates the expected CRM matching work item.
- [ ] Verify tenant isolation, score thresholds, and Telegram/panel notification evidence.

### Cross-System Contract Risks

- [ ] Test the wizard 422 path for missing `ilan_sahibi_id` and `danisman_id` relationships.
- [ ] Add type-normalization contract tests for `bina_yasi`, `kaks`, `ada_no`, and boolean feature values.
- [ ] Move image analysis, resizing, and WebP conversion to a queue-backed flow and measure timeout/retry behavior before enabling synchronous AI processing at scale.
- [ ] Confirm location plaka/ID reconciliation before TKGM polygon persistence or production location migration.
- [ ] Verify publication-gate behavior for categories whose feature templates are currently incomplete.

**Execution rule:** Do not begin Sprint 15 capability implementation until Priority 1 is certified or conditionally approved and Priority 2 critical runtime findings have an approved resolution or waiver.

**Certification boundary:** These items are repository findings, not production verification. No production write, deploy, migration, or seed is implied by this backlog.

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

## Cross-Cutting Workforce Hardening

| Item | Affected phase/sprint | Status | Exit evidence |
|------|------------------------|--------|---------------|
| Hermes Workforce runtime wiring | Sprint 14 → 15 | 🔴 BLOCKING REVIEW | PSR-4, constructor DI, and event-chain tests pass |
| Hermes Workforce test coverage | Sprint 14 → 15 | ⏳ OPEN | Five agent unit suites + one chain integration suite |
| Hermes technical-debt register | Phase 2 | ⏳ OPEN | 10 findings recorded with owner, severity, and remediation/waiver |

## Alternative Execution Path — Reliability First

Before expanding into new capabilities, the following reliability-first sequence is added to the Phase 2 worklist:

1. [ ] Fix the three critical Hermes runtime wiring findings.
2. [ ] Add unit tests for the five currently uncovered Workforce agents.
3. [ ] Add one end-to-end Workforce chain integration test.
4. [ ] Record all 10 Hermes technical-debt items with owner, priority, remediation, and waiver status.
5. [ ] Re-run Sprint 14 certification gates G-01 through G-04.
6. [ ] Start Sprint 15 Action Center only after Sprint 14 certification or an explicit board-approved conditional pass.

**Decision rule:** New capability work must not outrun unresolved critical runtime wiring or missing chain-level evidence. This path does not authorize production changes, migrations, seeds, deploys, or commits.

## Session 67 Follow-up Worklist

| Priority | Item | Owner | Status | Exit evidence |
|----------|------|-------|--------|---------------|
| P0 | G-04 Part 2 operator timing measurement in production | Authorized operator | OPEN | Timed production run recorded in `G-04-BAI-EVIDENCE.md` |
| P1 | Re-run Sprint 14 final certification after G-04 evidence | Certification owner | BLOCKED_ON_G04 | G-01 through G-04 certification decision |
| P1 | Isolate the five Workforce agent unit suites | Hermes engineering | PARTIAL | Independent fixtures and isolated tests for all five agents |
| P1 | Complete Workforce chain E2E evidence | Hermes engineering | PARTIAL | `portfolio.created` through notification event verified |
| P2 | Resolve or formally waive H-05, H-07, and H-10 | Hermes owner | OPEN / NON-BLOCKING | Buffer persistence, Drive async, and Telegram evidence or waiver |
| P2 | Start Sprint 15 Action Center only after Sprint 14 final certification | Architecture board | BLOCKED | Certified Sprint 14 or board-approved conditional pass |

Session 67 re-evaluation records H-01, H-02, and H-03 as false positives; H-04 and H-06 are closed. This update records backlog state only and authorizes no production change.
