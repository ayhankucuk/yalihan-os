# Check-in / Check-out Operations — Discovery Report

**Agent:** Kilo Code
**Model:** Claude Sonnet 4.6
**Role:** Discovery / Repository Analyst
**Mission:** Check-in / Check-out Operations Discovery
**Baseline:** `4a1784b`
**Mode:** READ-ONLY — No production code change
**Date:** 2026-08-15

---

## 1. Executive Summary

The reservation lifecycle is divided into two distinct systems:

- **Canonical reservation** → `PropertyReservation` (modern, event-driven)
- **Legacy reservation** → `YazlikRezervasyon` (separate table, parallel state machine)

**What is automated:**
- Reservation creation/modification/cancellation
- Availability blocking (channel sync)
- Guest confirmation (Wave 1 notification)

**What is NOT automated (entirely manual):**
- Pre-arrival property readiness (cleaning, inspection, pool check)
- Check-in instruction delivery (access codes, directions)
- Staff/cleaner task dispatch based on reservation dates
- Check-out processing (completion detection, financial settlement)
- Post-stay cleaning/turnover task lifecycle
- Property access credential delivery to guests

**Critical finding:** `ReservationCompletedEvent` is a canonical event designed for checkout, but it is **never dispatched** — `ReservationCompletionJob` does not exist. The entire checkout-to-settlement pipeline is stubbed out.

---

## 2. Lifecycle Model

```
┌─────────────────────────────────────────────────────────────────────┐
│  RESERVATION CREATED                                                │
│  PropertyReservation + PropertyAvailability (canonical)               │
│  Canonical events: ReservationCreatedEvent                           │
└────────────────────────────┬────────────────────────────────────────┘
                             │ after-commit
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PRE-ARRIVAL (between now and start_date)                           │
│  [MISSING] Pre-arrival property readiness tasks                      │
│  [MISSING] Cleaner/gardener assignment based on check-in date        │
│  [MISSING] Pre-arrival guest communication (check-in instructions)    │
│  [MISSING] Smart lock / access code provisioning                    │
└────────────────────────────┬────────────────────────────────────────┘
                             │ (start_date = today)
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  GUEST CHECK-IN                                                     │
│  [MISSING] checked_in_at timestamp on reservation                    │
│  [MISSING] Key/access handover event or job                         │
│  [MISSING] Guest receipt/confirmation of check-in                    │
│  PropertyAccessAsset exists (inventory) but NOT auto-assigned         │
│  AnahtarYonetimi: manual key handover, not triggered by system       │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STAY (guest in property)                                           │
│  [MISSING] Stay reminder / welcome message (Wave 3)                  │
│  [MISSING] Any operational automation during stay                    │
│  Channel: availability locked (blocked)                               │
└────────────────────────────┬────────────────────────────────────────┘
                             │ (end_date = today)
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  GUEST CHECK-OUT                                                    │
│  [MISSING] ReservationCompletedEvent dispatch (NO JOB EXISTS)         │
│  [MISSING] end_date < now() detection                               │
│  [MISSING] checked_out_at timestamp                                  │
│  [MISSING] ReservationState.COMPLETED enum value                     │
│  [MISSING] checkedOutCleanly flag consumption (stub in event)        │
│  [MISSING] Guest departure communication                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  INSPECTION & TURNOVER                                              │
│  [MISSING] Post-checkout inspection task creation                    │
│  [MISSING] Damage check workflow                                    │
│  [MISSING] Cleaning task dispatch to cleaner                        │
│  [MISSING] Pool/garden service trigger                              │
│  Gorev (task) system exists but NOT linked to reservation lifecycle  │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  CLEANING / TURNOVER COMPLETION                                     │
│  [MISSING] Cleaning completion verification                          │
│  [MISSING] Property readiness confirmation                          │
│  [MISSING] Availability unblocked for next reservation              │
│  Note: Availability is already blocked for next reservation —        │
│  but property may not actually be ready                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  READY (property prepared for next guest)                            │
│  Next ReservationCreatedEvent cycle                                  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Phase-by-Phase Analysis

### Phase 1: Reservation Confirmed

| Item | Finding |
|------|---------|
| **Current implementation** | `ReservationCreatedEvent` → `ProcessReservationCreated` → `SendGuestConfirmationJob` → WhatsApp/Email. Availability blocked via `AvailabilitySynchronizationService`. |
| **Canonical owner** | `PropertyReservation` (model) + `PropertyAvailability` (calendar) |
| **Date fields** | `start_date`/`end_date` (pure dates, no time). `check_in_time`/`check_out_time` on `Ilan` level (default 14:00/11:00). No guest-specific arrival time stored. |
| **Guest data** | `guest_name`, `guest_phone`, `guest_email` on `PropertyReservation` — **denormalized**, no `kisi_id` FK |
| **Missing capability** | Pre-arrival task generation (cleaning, pool, inspection). Cleaner assignment. Access code preparation. |
| **Event needed** | `ReservationConfirmedEvent` (new) to trigger pre-arrival pipeline |
| **Risk** | `ProcessReservationCreated:63` placeholder for "Stay Operation Task Generation" never implemented |

### Phase 2: Pre-Arrival

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no automated pre-arrival communication or task dispatch |
| **Canonical owner** | **MISSING** — no `PreArrivalReadinessService` or equivalent |
| **Staff assignment** | **NONE** — `GorevService.atamaYap()` is manual. `aiOtomatikAtama` is a placeholder. No `StaffAssignment` model linking cleaner to property. |
| **Missing capability** | Cleaner/gardener task creation. Check-in instruction message (Wave 2). Access code preparation. Property readiness checklist. |
| **Business automation opportunity** | Auto-create cleaning task: `check_in_date - 1 day` → dispatch `GorevCreated` → n8n → cleaner WhatsApp |
| **Event needed** | `PreArrivalReadinessTaskCreated` (new) |
| **Debt/Risk** | `YazlikRezervasyon` uses separate `check_in`/`check_out` dates — dual canonical risk |

### Phase 3: Property Readiness

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no property readiness model, no `ready_check` field, no cleaner assignment |
| **Property fields** | `Property` model: no `cleaner_id`, `manager_id`, `ready_check` fields |
| **Cleaning fee** | `cleaning_fee` on `Ilan` — financial field only, NOT an operational task trigger |
| **Gorev types** | `musteri_takibi`, `ilan_hazirlama`, `musteri_ziyareti`, `diger` — none are property operational (cleaning, pool, garden) |
| **Missing capability** | Property readiness state machine: `not_ready → cleaning_scheduled → cleaning_in_progress → inspected → ready` |
| **Business automation opportunity** | Auto-inspection workflow: cleaner completes task → manager approves → property marked ready |
| **Risk** | `PropertyAvailability` blocks calendar dates, but property may physically not be ready |

### Phase 4: Guest Check-in

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no check-in event, no timestamp, no state transition |
| **Timestamp** | `checked_in_at` **MISSING** on `PropertyReservation`. `ReservationState` has no `CHECKED_IN` value. |
| **Access infrastructure** | `PropertyAccessAsset` (inventory) + `PropertyKeyCustody` (immutable custody log) exist (Sprint 12D). `tanimlayici_no` stores actual code. `getCredentialForViewer()` restricted to admin only. |
| **Access delivery** | **MISSING** — no job/service sends access codes to guest (no WhatsApp, SMS, or email of door codes) |
| **Key handover** | `AnahtarYonetimi` (legacy): manual, not triggered by system |
| **Missing capability** | Check-in instruction delivery. Access code/Smart Lock PIN transmission to guest. Key handover confirmation. |
| **Business automation opportunity** | Auto-send check-in instructions + access codes via WhatsApp at `check_in_time` on `start_date` |
| **Event needed** | `GuestCheckedInEvent` (new) |
| **Risk** | No Airbnb Hosty/Oper/Breezeway integration for smart lock provisioning |

### Phase 5: Stay

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no stay-related automation. Channel availability locked. |
| **Missing capability** | Stay reminder/welcome message (Wave 3). Guest comfort requests. Stay issue reporting. |
| **Guest communication** | Only `reservation_confirmation` template implemented (Wave 1). Waves 2–5 in `ProcessReservationCreated:61` TODO block |
| **Risk** | Guest has no automated contact during stay unless staff manually sends WhatsApp |

### Phase 6: Guest Check-out

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — entire checkout pipeline is stub-only |
| **Dispatch trigger** | `ReservationCompletedEvent` defined but **never dispatched** — `ReservationCompletionJob` does not exist |
| **Completion detection** | **NO CODE** implements `end_date < now()` detection. No scheduled command. No daily job. |
| **State transition** | `ReservationState` has no `COMPLETED` or `CHECKED_OUT`. No `completed_at` on `PropertyReservation` |
| **Financial settlement** | `ReservationCompletionLedgerService` container binding exists but **service file does not exist**. Ledger system exists standalone but not wired to checkout |
| **Departure reminder** | **MISSING** — Wave 4 (departure reminder) not implemented |
| **checkedOutCleanly** | Stub field in event — set but **zero consumers** |
| **Missing capability** | Automatic checkout detection. Financial closure. Owner payout. Commission. Guest departure reminder. |
| **Business automation opportunity** | Auto-detect `end_date = today` → dispatch `ReservationCompletedEvent` → trigger settlement + review request |
| **Event needed** | `ReservationCompletedEvent` is already defined — needs only a scheduled job to fire it |
| **Risk** | High: no financial closure means no automatic owner payout or commission tracking at checkout |

### Phase 7: Inspection / Turnover

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no inspection model, no turnover task, no damage report |
| **Task model** | `Gorev` (task) exists but unlinked to reservation lifecycle. Task types: `musteri_takibi`, `ilan_hazirlama` — none for operational turnover |
| **Staff notification** | n8n webhook pipeline for `GorevCreated` exists (Telegram/WhatsApp/Email to staff) |
| **Missing capability** | Post-checkout inspection task creation. Damage check. Cleaning task dispatch to cleaner. Pool service. Garden service |
| **Business automation opportunity** | Auto-create turnover Gorev on `ReservationCompletedEvent` → n8n → cleaner WhatsApp with checklist |
| **Event needed** | `CheckoutTurnoverTaskCreated` (new) |
| **Risk** | `PropertyAvailability` keeps next reservation's dates blocked — but property may not actually be cleaned |

### Phase 8: Cleaning Completion → Ready

| Item | Finding |
|------|---------|
| **Current implementation** | **NONE** — no cleaning completion verification, no property readiness state |
| **Gorev completion** | `GorevDurumChanged` → n8n webhook → staff notification pipeline exists |
| **Missing capability** | Property readiness confirmation after cleaning. Manager inspection approval. Automatic availability for next guest. |
| **Business automation opportunity** | Gorev completion → `PropertyReadinessConfirmed` event → property marked ready → optionally notify next guest's arrival |
| **Risk** | Next guest could arrive to find property not actually ready |

---

## 4. Canonical Event Architecture — Current State

```
EXISTING (wired):
  ReservationCreatedEvent       → ProcessReservationCreated
  ReservationModifiedEvent      → ProcessReservationModified
  ReservationCancelledEvent    → ProcessReservationCancelled

EXISTS (not wired — stub):
  ReservationCompletedEvent    → NO LISTENER, NO JOB
  GuestCheckedInEvent          → DOES NOT EXIST
  PreArrivalTaskCreatedEvent   → DOES NOT EXIST
  TurnoverTaskCreatedEvent     → DOES NOT EXIST
  PropertyReadyEvent           → DOES NOT EXIST
```

---

## 5. Guest Communication Waves — Current State

| Wave | Description | Status | Implementation |
|------|-------------|--------|----------------|
| 1 | Confirmation (WhatsApp/Email) | ✅ LIVE | `SendGuestConfirmationJob` |
| 2 | Pre-arrival check-in instructions | ❌ NOT DONE | TODO `ProcessReservationCreated:61` |
| 3 | Stay reminder / welcome | ❌ NOT DONE | — |
| 4 | Departure reminder | ❌ NOT DONE | — |
| 5 | Post-stay review request | ❌ NOT DONE | `ReservationCompletedEvent` not wired |

**Modification/cancellation notifications:** Also TODO stubs in `ProcessReservationModified` and `ProcessReservationCancelled`.

---

## 6. Tenant Isolation Assessment

| Component | Tenant-scoped | Notes |
|-----------|--------------|-------|
| `PropertyReservation` | ✅ Yes | `tenant_id` filter on all queries |
| `Gorev` (Task) | ✅ Yes | `tenant_id` via `workspace_id` |
| `PropertyAccessAsset` | ✅ Yes | `tenant_id` on model |
| `PropertyKeyCustody` | ✅ Yes | Immutable, tenant-scoped |
| `Ilan` | ✅ Yes | `tenant_id` |
| `PropertyAvailability` | ✅ Yes | Tenant-scoped canonical |
| Pre-arrival tasks | ⚠️ N/A | Not built yet |
| Turnover tasks | ⚠️ N/A | Not built yet |

**Assessment:** Any new Check-in/Check-out automation must enforce tenant isolation via existing patterns (global scopes, `TenantScope`, `TenantManager`). The infrastructure patterns are well-established.

---

## 7. Idempotency / Replay / Evidence Assessment

| Pattern | Status | Implementation |
|---------|--------|----------------|
| Idempotency | ✅ Established | Channel sync uses `ChannelSyncExecution` + idempotency keys. Guest notifications use `OutboundNotification.isAlreadySent()` check |
| Replay | ✅ Established | `AvailabilitySynchronizationService.replay()` creates new `ChannelSyncExecution` |
| Evidence | ✅ Established | `ChannelSyncExecution` audit trail: `attempts`, `status`, `channel_error`, `completed_at` |
| Check-in/out replay | ⚠️ N/A | Not built — must adopt same patterns for new operations |

**Recommendation:** New check-in/check-out jobs should adopt the same `Execution`-based idempotency model. Pre-arrival task creation should check `gorev_idempotency_key = reservation_id + task_type` before creating.

---

## 8. Existing Infrastructure — Leveraged Assets

The following existing infrastructure can be **directly reused** for Check-in/Check-out automation:

| Asset | Location | Reuse For |
|-------|----------|-----------|
| `ReservationCreatedEvent` | `app/Events/Reservation/` | Trigger pre-arrival pipeline |
| `PropertyAccessAsset` + `PropertyKeyCustody` | `app/Domain/PropertyAccess/` | Access code inventory + custody log |
| `Gorev` (Task) + `GorevCreated` event | `app/Modules/TakimYonetimi/` | Turnover task creation |
| n8n webhook jobs | `app/Jobs/NotifyN8n*` | Staff/cleaner notifications |
| `NotificationDispatcher` + WhatsApp/Email adapters | `app/Services/Notification/` | Check-in instructions, reminders |
| `GuestCommunicationPolicy` (consent, idempotency) | `app/Services/Notification/` | Guest messaging policy |
| `OutboundNotification` audit model | `app/Models/Notification/` | Message audit trail |
| `AvailabilitySynchronizationService` (pattern) | `app/Services/` | Reference for queue-first + retry + evidence pattern |
| Tenant isolation (global scopes) | `app/Scopes/TenantScope.php` | Enforce tenant in all queries |

---

## 9. SAAB Architectural Decision Questions

> These questions must be answered before Check-in/Check-out automation can be designed and certified.

### Q1 — State Machine: Reservation Completion
**Question:** Should `PropertyReservation` gain a `COMPLETED` / `CHECKED_OUT` state in `ReservationState` enum, or should completion be purely event-driven (no state change, only `completed_at` timestamp)?

**Trade-offs:**
- State change → explicit, queryable, audit-able. But requires migration + all existing code that checks `reservation_state` to be reviewed.
- Event-driven → less invasive, matches the canonical event pattern already established. But completion is invisible in the state machine.

**SAAB must decide:** Which pattern is canonical for Yalihan OS lifecycle completion?

---

### Q2 — Canonical Owner: Check-in Time
**Question:** `check_in_time` lives on `Ilan` (listing) today, not on `PropertyReservation`. Should the **guest's specific arrival time** (if different from property default) be stored on the reservation record?

**Trade-offs:**
- Store on reservation → supports guest-specific arrivals, flexible for multi-unit properties. But requires `giris_saati` column migration.
- Keep on Ilan → simpler, property-level default. But cannot capture guest-specific preferences.

**SAAB must decide:** Is guest arrival time a reservation attribute or a property attribute?

---

### Q3 — Cleaning Task Model: Gorev or Dedicated?
**Question:** Should turnover/cleaning tasks use the existing `Gorev` model (with a new task type) or a dedicated `CleaningTask` / `TurnoverTask` model?

**Trade-offs:**
- Use `Gorev` → reuse existing n8n webhook pipeline, single task system, no new model. But `Gorev` currently serves CRM/office tasks — mixing operational field tasks may confuse `oncelik`/`gorev_tipi` taxonomy.
- Dedicated model → clean domain separation, specialized fields (cleaning checklist, room inspection, pool status). But requires new infrastructure (service, job, event, listener).

**SAAB must decide:** Consolidate on Gorev or create a dedicated operational task model?

---

### Q4 — Completion Trigger: Pull or Push?
**Question:** `ReservationCompletedEvent` requires a daily scheduled job to detect `end_date = today`. Should this be a **Laravel scheduled command** (runs once/day, checks all reservations) or an **event-driven trigger** (availability unblocking implicitly marks checkout)?

**Trade-offs:**
- Scheduled command → simple, reliable. But latency: max 24h delay between checkout and processing.
- Event-driven → faster, real-time. But requires availability sync to emit a "checkout processed" signal.

**SAAB must decide:** Is same-day financial settlement required, or is nightly batch acceptable?

---

### Q5 — Access Code Delivery: Platform or Yalihan OS?
**Question:** Should Yalihan OS **deliver** access codes to guests (via WhatsApp/email using `PropertyAccessAsset.tanimlayici_no`) or should this integrate with an external platform (Airbnb Hosty, Oper, Breezeway, Smart Lock API)?

**Trade-offs:**
- Internal delivery → Yalihan OS owns the full chain, no external dependency. `PropertyAccessAsset` already exists. But requires manual code entry into the system.
- Platform integration → automated for OTA channels (Airbnb sends guest details to Hosty/Oper automatically). But introduces external service dependency and API complexity.

**SAAB must decide:** Build internal access code delivery or integrate with an external access management platform?

---

### Q6 — Financial Settlement: When?
**Question:** When should owner payout / commission calculation be triggered: at **checkout** (`end_date`) or at **checkout confirmation** (when cleaner marks property ready)?

**Trade-offs:**
- At checkout (`end_date`) → matches OTA channel settlement timing, simpler trigger. But cleaner may not have finished turnover.
- At readiness confirmation → ensures property was actually cleaned/inspected before financial close. But introduces dependency on cleaning task completion.

**SAAB must decide:** Is financial settlement tied to guest departure or to property readiness confirmation?

---

### Q7 — Idempotency: Per-Reservation or Per-Stay?
**Question:** For pre-arrival tasks and check-in communications, should idempotency keys be `reservation_id + task_type` (supports modification) or `reservation_id + date + task_type` (supports same-property overlapping reservations)?

**Trade-offs:**
- Per-reservation → simpler, supports reservation modifications. But if dates change, old task may be orphaned.
- Per-stay → more precise, prevents duplicate tasks for overlapping stays on same property. But more complex key generation.

**SAAB must decide:** What is the correct idempotency granularity for check-in/out operational tasks?

---

### Q8 — `YazlikRezervasyon` Legacy: Migrate or Parallel Track?
**Question:** `YazlikRezervasyon` is a parallel reservation model with its own state machine (`beklemede`/`onaylandi`/`iptal`/`tamamlandi`) and separate `check_in`/`check_out` date fields. Should Check-in/Check-out automation be built for `PropertyReservation` only (modern path), or should both models be supported?

**Trade-offs:**
- Modern path only → simpler, cleaner. But leaves legacy reservations without automation.
- Both models → more complete coverage. But doubles the implementation scope and maintenance burden.

**SAAB must decide:** Is there a migration path from `YazlikRezervasyon` to `PropertyReservation`, or are they permanently parallel?

---

## 10. SAAB Business Rule Compliance

**Every implementation must answer:** *What manual real estate work disappears after this change?*

### Manual work that DISAPPEARS after Check-in/Check-out automation:

| Manual Work | How Automated |
|-------------|---------------|
| Staff manually creates cleaning tasks for each reservation | `ReservationConfirmedEvent` → `ProcessReservationCreated` → creates `Gorev` for cleaner |
| Ayhan manually sends check-in instructions via WhatsApp | `start_date - 24h` scheduled job → `NotificationDispatcher` → WhatsApp |
| Ayhan manually sends access codes to guest | `PropertyAccessAsset.tanimlayici_no` → `NotificationDispatcher` → WhatsApp/Email |
| Ayhan manually notifies cleaner of next guest arrival | `GorevCreated` → n8n webhook → Telegram/WhatsApp to cleaner |
| Ayhan manually tracks who has the property keys | `PropertyKeyCustody` immutable log (already exists, just needs triggering) |
| Ayhan manually marks reservation as completed | `ReservationCompletionJob` (daily) → `ReservationCompletedEvent` |
| Ayhan manually creates financial entries on checkout | `ReservationCompletedEvent` → `ReservationCompletionLedgerService` |
| Ayhan manually sends departure reminder | `end_date - 24h` scheduled job → `NotificationDispatcher` → WhatsApp |
| Ayhan manually follows up on cleaning after checkout | `ReservationCompletedEvent` → creates turnover `Gorev` → n8n → cleaner |
| Ayhan manually confirms property ready for next guest | `Gorev` completion → `PropertyReadyEvent` → availability pipeline |

**Estimated manual time saved per reservation:** ~20–40 minutes of Ayhan/staff communication overhead.

---

## 11. Discovery Metadata

| Item | Value |
|------|-------|
| Models analyzed | `PropertyReservation`, `YazlikRezervasyon`, `Ilan`, `Property`, `Gorev`, `PropertyAccessAsset`, `PropertyKeyCustody`, `OutboundNotification` |
| Events analyzed | 9 reservation events, 6 Gorev events, 5 channel manager events |
| Jobs analyzed | 6 reservation jobs, 6 n8n webhook jobs, 4 notification jobs |
| Services analyzed | 12 services across reservation, notification, access, availability |
| Files examined | ~40 core files across Models, Events, Jobs, Services |
| Scheduled commands reviewed | 22 commands in `app/Console/Kernel.php` |
| Production code changed | 0 |
| Discovery agent sessions | 4 parallel agents (lifecycle, guest comm, property ops, completion) |
| SAAB questions generated | 8 |

---

**Status:** Discovery complete. Ready for SAAB review + Antigravity/Gemini adversarial review.
