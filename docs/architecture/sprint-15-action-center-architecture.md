# Sprint 15 — Action Center Architecture Prerequisites

**Document:** `docs/architecture/sprint-15-action-center-architecture.md`
**Date:** 2026-09-06
**Agent:** Architect (Kodex)
**Context:** PHASE2-ROADMAP.md Priority 5 — Sprint 15/16 Architecture Prerequisites
**Evidence Level:** `REPO_VERIFIED` (codebase analysis; no production write)
**Status:** ✅ ARCHITECTURE COMPLETE

---

## 1. Sprint 15 Exit Question

> "Sistem yapılacak işleri otomatik üretip önceliklendiriyor mu?"

**Answer requirement:** Domain events → automatic Gorev creation → priority scoring → assignment → tracking → notification → evidence.

---

## 2. Current Infrastructure Inventory

### 2.1 Gorev (Task) Model — `app/Modules/TakimYonetimi/Models/Gorev.php`

**Table:** `gorevler` (baseline migration #22 + 3 additive migrations)

| Column | Type | Purpose |
|---|---|---|
| `baslik` | string | Task title |
| `aciklama` | text | Description |
| `gorev_durumu` | string | Lifecycle: bekliyor, devam_ediyor, tamamlandi, iptal, beklemede |
| `oncelik` | string | Priority: acil, yuksek, normal, dusuk |
| `atanan_user_id` | FK→users | Assigned agent |
| `olusturan_user_id` | FK→users | Creator (0 = system/auto) |
| `kisi_id` | FK→kisiler | CRM contact link |
| `lead_id` | FK→leads | CRM lead link |
| `proje_id` | FK→projeler | Project link |
| `ilan_id` | FK→ilanlar | Property link (added 2026-08-16) |
| `reservation_id` | FK→property_reservations | Reservation link (added 2026-08-16) |
| `gorev_tipi` | string | Task type: musteri_takibi, ilan_hazirlama, hazirlik, temizlik, kontrol, havuz, bahce, diger |
| `baslangic_tarihi` | date | Start date |
| `bitis_tarihi` | date | Deadline |
| `tamamlanma_yuzdesi` | integer | Progress 0-100 |

**Lifecycle methods:** `tamamla()`, `iptalEt()`, `atanabilirMi()`, `tamamlanabilirMi()`, `geciktiMi()`, `deadlineYaklasiyorMu()`

**Scopes:** `aktif`, `geciken`, `deadlineYaklasan`, `forReservation`, `forIlan`, `operational`, `byType`, `byDurum`

### 2.2 Domain Event Map — `app/Providers/EventServiceProvider.php`

#### Listing Events (5 events, 7 listeners)
| Event | Listeners | Action Center Potential |
|---|---|---|
| `IlanCreated` | BC001Listener, FindMatchingDemands, InvalidateIlanCache, SendEmailOnIlanCreated, UpdateAnalyticsProjections | HIGH — new listing → "fotoğraf yükle", "açıklama yaz", "fiyatlandır" |
| `IlanUpdated` | InvalidateIlanCache, UpdateAnalyticsProjections | LOW — update tracking |
| `IlanDeleted` | InvalidateIlanCache, UpdateAnalyticsProjections | LOW — cleanup |
| `IlanYayinlandiEvent` | NotifyLeadsOnNewListing | HIGH — published → "lead matching", "channel sync" |
| `IlanPriceChanged` | NotifyN8nOnIlanPriceChanged | MEDIUM — price change → "re-evaluate matching" |

#### Reservation Events (6 events, 10 listeners)
| Event | Listeners | Action Center Potential |
|---|---|---|
| `ReservationCreatedEvent` | ListenReservationCreated, ListenReservationCreatedReadiness | HIGH — **already creates Gorev** via CreateOperationalTasksJob |
| `ReservationModifiedEvent` | ListenReservationModified, ListenReadinessOnDateChange, CancelPendingCredentialNotifications | MEDIUM — date change → "update readiness" |
| `ReservationCancelledEvent` | ListenReservationCancelled, ListenReadinessOnCancellation, CancelPendingCredentialNotifications, ListenCancellationCommunication | HIGH — "refund processing", "cancel readiness" |
| `ReservationCompletedEvent` | ListenReservationCompleted | MEDIUM — "post-stay inspection", "review request" |
| `ReservationPayoutReadyEvent` | ListenPayoutReady | HIGH — "process payout" |
| `CheckinWindowOpenedEvent` | ListenCheckinWindowOpened | MEDIUM — "send credentials", "final readiness check" |

#### CRM/Lead Events (4 events, 6 listeners)
| Event | Listeners | Action Center Potential |
|---|---|---|
| `LeadOlusturuldu` | AutoReplyToLeadCreation, ProcessNewLeadForCRM, NotifyAdminsOnNewLead, EvaluateLeadWithCortex | HIGH — new lead → "contact within SLA", "qualify" |
| `LeadDurumDegisti` | (empty) | MEDIUM — status change → "next action" |
| `LeadAgentAtandi` | NotifyAgentOnLeadAssignment | HIGH — "contact lead", "schedule viewing" |
| `TalepReceived` | AnalyzeAndPrioritizeDemand | HIGH — new demand → "match listings", "contact buyer" |

#### Task Events (4 events, 4 listeners)
| Event | Listeners | Action Center Potential |
|---|---|---|
| `GorevCreated` | NotifyN8nOnGorevCreated | LOW — notification only |
| `GorevDurumChanged` | NotifyN8nOnGorevDurumChanged, ListenGorevReadinessUpdate | LOW — notification + readiness |
| `GorevDeadlineYaklasiyor` | NotifyN8nOnGorevDeadlineYaklasiyor | LOW — reminder |
| `GorevGecikti` | NotifyN8nOnGorevGecikti | LOW — escalation |

#### Hermes Workforce Events (5 events, 5 handlers)
| Event | Handler | Action Center Potential |
|---|---|---|
| `PropertyWorkspaceCreated` | DriveAgent | MEDIUM — "upload photos", "organize drive" |
| `PhotoAnalysisCompleted` | DescriptionAgent | HIGH — "review AI description", "approve/reject" |
| `DescriptionCompleted` | PropertyScoreAgent | MEDIUM — "review score" |
| `PropertyScoreCalculated` | PublishDecisionAgent | HIGH — "approve publication", "fix blocking issues" |
| `PublishingDecisionReady` | NotificationAgent | HIGH — "take action on decision" |

### 2.3 AdvisorCommandCenterService — `app/Services/AI/AdvisorCommandCenterService.php`

**Current state:** Stateless priority action aggregator
- 4 modules: DealRadar, OpportunityEngine, PortfolioDoctor, BuyerMatch
- `buildPriorityActions()` — aggregates actions from all modules
- `normalizeActionPriority()` — maps to CRITICAL/HIGH/MEDIUM/LOW
- **Gap:** Actions are NOT persisted to `gorevler` — they're computed on every `/fetch` request

### 2.4 CreateOperationalTasksJob — `app/Jobs/Reservation/CreateOperationalTasksJob.php`

**Pattern:** Event → Job → Service → Gorev
- Triggered by: `ReservationCreatedEvent` (via `ProcessReservationCreated`)
- Calls: `OperationalGorevService::createPreArrivalTask()`
- Idempotent: checks if task already exists before creating
- Tenant-scoped: verifies `tenant_id` on reservation and ilan
- Retryable: 3 tries with [30, 60, 120] backoff

**This is the reference pattern for Action Center task generation.**

---

## 3. Action Center Architecture — Domain Event → Work Item Mapping

### 3.1 Event-to-Action Mapping Contract

| Domain Event | Action Type | Priority | gorev_tipi | Auto-Assign | Deadline |
|---|---|---|---|---|---|
| `IlanCreated` | `ilan_foto_yukle` | yuksek | ilan_hazirlama | listing owner | +24h |
| `IlanCreated` | `ilan_aciklama_yaz` | yuksek | ilan_hazirlama | listing owner | +48h |
| `IlanCreated` | `ilan_fiyatlandir` | normal | ilan_hazirlama | listing owner | +72h |
| `IlanYayinlandiEvent` | `lead_matching_check` | yuksek | musteri_takibi | system | +1h |
| `IlanPriceChanged` | `re_evaluate_matching` | normal | diger | system | +4h |
| `ReservationCreatedEvent` | `hazirlik` (existing) | yuksek | hazirlik | cleaner | check-in -1d |
| `ReservationCancelledEvent` | `cancel_readiness` | acil | kontrol | cleaner | immediate |
| `ReservationCompletedEvent` | `post_stay_inspection` | normal | kontrol | cleaner | checkout +1d |
| `ReservationPayoutReadyEvent` | `process_payout` | acil | diger | finance | +24h |
| `LeadOlusturuldu` | `contact_lead_sla` | acil | musteri_takibi | round-robin | +2h |
| `LeadAgentAtandi` | `contact_assigned_lead` | yuksek | musteri_takibi | assigned agent | +4h |
| `TalepReceived` | `match_demand_to_listings` | yuksek | musteri_takibi | system | +4h |
| `PublishingDecisionReady` | `review_publication_decision` | yuksek | ilan_hazirlama | listing owner | +24h |
| `PhotoAnalysisCompleted` | `review_ai_description` | normal | ilan_hazirlama | listing owner | +48h |

### 3.2 Action Center Service Contract

```
ActionCenterService
├── generateActionsFromEvent(DomainEvent $event): Collection<Gorev>
├── prioritizeActions(Collection<Gorev>): Collection<Gorev>
├── assignAction(Gorev $gorev, User|int $agent): Gorev
├── trackActionEvidence(Gorev $gorev, array $evidence): void
├── getActionQueue(int $tenantId, array $filters): LengthAwarePaginator
├── getOverdueActions(int $tenantId): Collection
└── escalateAction(Gorev $gorev, string $reason): Gorev
```

### 3.3 Priority Scoring Business Rules

| Signal | Priority | Score |
|---|---|---|
| SLA breach (lead not contacted in 2h) | acil | 100 |
| Reservation cancelled (refund needed) | acil | 95 |
| Payout ready | acil | 90 |
| New lead (within SLA) | acil | 85 |
| New listing (no photos) | yuksek | 75 |
| Publishing decision pending | yuksek | 70 |
| Demand received | yuksek | 65 |
| Price change (re-match needed) | normal | 50 |
| Post-stay inspection | normal | 40 |
| AI description review | normal | 30 |

---

## 4. Action Center Lifecycle Contract

```
                    ┌─────────────┐
                    │  GENERATED  │ ← Event triggers creation
                    └──────┬──────┘
                           │ assign
                    ┌──────▼──────┐
                    │  ASSIGNED   │ ← Agent assigned (auto or manual)
                    └──────┬──────┘
                           │ start
                    ┌──────▼──────┐
                    │ IN_PROGRESS │ ← Agent starts working
                    └──────┬──────┘
                      │         │
           complete   │         │ cancel
                    ┌──▼──┐  ┌──▼──┐
                    │ DONE│  │CANCEL│
                    └────┘  └─────┘
```

**Evidence requirements:**
- `GENERATED → ASSIGNED`: record `assigned_by`, `assigned_at`
- `ASSIGNED → IN_PROGRESS`: record `started_at`
- `IN_PROGRESS → DONE`: record `completed_at`, `completion_evidence` (photo, note, system log)
- `ANY → CANCEL`: record `cancel_reason`, `cancelled_by`

---

## 5. Knowledge Core AI Contract (Sprint 16 Prerequisites)

### 5.1 Provenance Contract

Every AI-generated action must carry:
- `source_event` — which domain event triggered this action
- `source_module` — which AI module suggested it (DealRadar, OpportunityEngine, etc.)
- `ai_confidence_score` — confidence 0.0-1.0
- `ai_reasoning` — human-readable explanation
- `ai_model_version` — model identifier for audit trail

### 5.2 Explainability Contract

```
Action {
  id: int
  title: string
  reason: string          // "Fiyat piyasa ortalamasının %15 altında"
  source: string          // "DealRadar"
  confidence: float      // 0.87
  evidence_refs: array   // [listing_id, comparable_ids, market_data_snapshot]
  created_by: "system"   // system | user_id
  created_at: timestamp
}
```

---

## 6. Implementation Roadmap

### Phase 1 — Event-to-Action Mapping (P5.1)
1. Create `ActionCenterService` — central orchestrator
2. Create `ActionGeneratorListener` for each domain event family
3. Register listeners in `EventServiceProvider`
4. Each listener calls `ActionCenterService::generateActionsFromEvent()`
5. Service creates `Gorev` records with proper `gorev_tipi`, `oncelik`, deadlines

### Phase 2 — Priority & Assignment (P5.2)
1. Implement `prioritizeActions()` — business rule engine
2. Implement `assignAction()` — auto-assignment rules (round-robin, workload balance)
3. Add `action_center` route group + controller
4. Add Action Center dashboard view (priority queue, overdue, assigned-to-me)

### Phase 3 — Evidence & Tracking (P5.2)
1. Add `action_evidence` table — photo, note, system log per action
2. Implement `trackActionEvidence()`
3. Add lifecycle state machine enforcement
4. Add escalation rules (overdue → reassign, SLA breach → notify manager)

### Phase 4 — Knowledge Core Integration (Sprint 16)
1. Add AI provenance fields to `gorevler` table (migration)
2. Wire `AdvisorCommandCenterService` modules as action sources
3. Implement explainability API endpoint
4. Add human-in-the-loop approval workflow

---

## 7. Schema Changes Required

### 7.1 `gorevler` Table — Additive Migration

```php
// 2026_09_XX_000001_add_action_center_fields_to_gorevler.php
$table->string('source_event')->nullable()->after('gorev_tipi');        // domain event class
$table->string('source_module')->nullable()->after('source_event');     // AI module name
$table->float('ai_confidence_score')->nullable()->after('source_module');
$table->text('ai_reasoning')->nullable()->after('ai_confidence_score');
$table->string('ai_model_version')->nullable()->after('ai_reasoning');
$table->timestamp('assigned_at')->nullable()->after('atanan_user_id');
$table->timestamp('started_at')->nullable()->after('assigned_at');
$table->timestamp('completed_at')->nullable()->after('started_at');
$table->string('cancel_reason')->nullable()->after('gorev_durumu');
$table->index(['gorev_durumu', 'oncelik', 'bitis_tarihi'], 'action_center_queue_idx');
```

### 7.2 `action_evidence` Table — New

```php
Schema::create('action_evidence', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gorev_id')->constrained('gorevler')->cascadeOnDelete();
    $table->string('evidence_type'); // photo, note, system_log, screenshot
    $table->text('evidence_data');   // JSON payload
    $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index('gorev_id');
});
```

---

## 8. Dependencies & Risks

| Dependency | Status | Risk |
|---|---|---|
| Sprint 14 certified | ✅ CONDITIONAL_CERTIFIED | LOW — G-04 Part 2 pending (operator) |
| Gorev model exists | ✅ REPO_VERIFIED | LOW |
| gorevler table has operational fields | ✅ REPO_VERIFIED | LOW |
| CreateOperationalTasksJob pattern | ✅ REPO_VERIFIED | LOW — reference pattern available |
| AdvisorCommandCenterService | ✅ REPO_VERIFIED | MEDIUM — needs refactor from stateless to persistent |
| Hermes event bus | ✅ REPO_VERIFIED | LOW |
| Tenant isolation | ✅ REPO_VERIFIED | LOW — existing pattern in CreateOperationalTasksJob |

---

## 9. Acceptance Criteria

- [ ] `ActionCenterService` created with 7 methods (generate, prioritize, assign, track, getQueue, getOverdue, escalate)
- [ ] Event-to-action mapping for 14 domain events (see §3.1)
- [ ] Priority scoring business rules implemented (see §3.3)
- [ ] Action lifecycle state machine enforced (see §4)
- [ ] Schema migration for `gorevler` additive fields (see §7.1)
- [ ] `action_evidence` table created (see §7.2)
- [ ] Integration test: `IlanCreated` → 3 Gorev records created
- [ ] Integration test: `LeadOlusturuldu` → 1 Gorev with deadline +2h
- [ ] Integration test: `ReservationCreatedEvent` → existing pattern still works
- [ ] Tenant isolation test: cross-tenant actions not visible
- [ ] `docs/ERA_V/PHASE2-ROADMAP.md` Priority 5 items marked complete

---

## 10. Next Steps

1. **Kodex (Code mode):** Implement Phase 1 — `ActionCenterService` + event listeners
2. **Kodex (Code mode):** Implement Phase 2 — Priority engine + auto-assignment
3. **Kodex (Code mode):** Implement Phase 3 — Evidence table + lifecycle enforcement
4. **Sprint 16:** Phase 4 — Knowledge Core AI integration (separate sprint)
