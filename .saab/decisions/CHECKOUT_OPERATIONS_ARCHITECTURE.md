# SAAB Architecture Decision — Check-in / Check-out Operations

**Board:** SAAB — Strategic Architecture Board v11.1
**Decision Date:** 2026-08-16
**Baseline:** `fd70ecc` (Discovery complete)
**Author:** SAAB Architecture Board
**Model:** Claude Opus 4.8
**Mode:** Decision Authority — Normative

---

## Preamble

Sonnet 4.6 discovery (`fd70ecc`) and Gemini adversarial audit have established the current state:

- **Canonical reservation** → `PropertyReservation` (event-driven, modern)
- **Operational tasks** → `Gorev` model + n8n webhook pipeline (exists, unlinked)
- **Access infrastructure** → `PropertyAccessAsset` + `PropertyKeyCustody` (Sprint 12D, exists)
- **Financial ledger** → `ReservationCompletionLedgerService` is a **ghost binding** — container registration exists in `AppServiceProvider:177-178` but the service file does not exist anywhere in `app/Services/Finance/`.

Eight architectural questions were raised. Four are resolved by existing architecture (Q2, Q4, Q5, Q7). Three require explicit SAAB decisions (Q1, Q3, Q6). One requires implementation scoping (Q8).

This document resolves **Q1, Q3, and Q6 only**.

---

## Q1 — Lifecycle Representation

### Analysis

**Current state:**
- `ReservationState` enum: `PENDING`, `CONFIRMED`, `BLOCKED`, `CANCELLED`
- Semantics: `PENDING` = awaiting confirmation, `CONFIRMED` = payment/review approved, `BLOCKED` = conflict override, `CANCELLED` = guest/owner cancelled
- `cancelled_at` and `confirmed_at` timestamps exist as soft markers
- No `COMPLETED`, `CHECKED_IN`, or `CHECKED_OUT` value in the enum

**Option A — Extend ReservationState enum:**
Adding `CHECKED_IN`, `CHECKED_OUT`, `COMPLETED` would alter the semantic domain of the enum. The existing states represent **booking lifecycle** (can this reservation proceed?), not **operational lifecycle** (is the guest in the property?). Mixing these concerns violates Single Responsibility at the enum level.

**Risk of Option A:** Any code that iterates `ReservationState::cases()`, switches on all values, or validates against known states would break silently. In a 211-model, 384-service codebase, this is a non-trivial blast radius.

**Option B — Timestamps + Events (recommended):**
`PropertyReservation` already has `cancelled_at` and `confirmed_at` as timestamp markers. The same pattern extends naturally:

- `checked_in_at` (datetime, nullable) — set when guest is confirmed in property
- `checked_out_at` (datetime, nullable) — set when guest departs
- `completed_at` (datetime, nullable) — set when all post-checkout processing is done

These are **immutable facts**, not state transitions. Querying them is deterministic:
```php
// Guest currently in property?
$inProperty = $reservation->start_date <= today() && $reservation->end_date > today()
    && $reservation->checked_in_at !== null
    && $reservation->checked_out_at === null;
```

The canonical events (`ReservationCheckedInEvent`, `ReservationCheckedOutEvent`, `ReservationCompletedEvent`) carry the timestamps and drive downstream systems. The state machine remains unchanged — its job is booking lifecycle, not operational lifecycle.

**Option C — Separate operational state aggregate:**
Creates a new `ReservationOperation` aggregate with its own state machine. This is the cleanest domain model but introduces significant complexity: dual state synchronization, eventual consistency between two aggregates, new service and repository layer. This complexity is unjustified for Wave 1.

### Decision

**APPROVED: Option B**

---

## CHECKOUT-D1 — Lifecycle Representation

| Field | Value |
|-------|-------|
| **Approved Option** | B — Timestamps + Events |
| **Rationale** | Preserves `ReservationState` semantic boundary (booking lifecycle). Adds operational timestamps (`checked_in_at`, `checked_out_at`, `completed_at`) as immutable facts on `PropertyReservation`. Drives downstream systems via canonical events. Zero state machine changes. |
| **MUST** | All operational lifecycle queries use timestamp fields, not enum values |
| **MUST** | `ReservationCheckedInEvent`, `ReservationCheckedOutEvent`, `ReservationCompletedEvent` carry timestamp in event payload |
| **MUST NOT** | Extend `ReservationState` enum with operational states |
| **MUST NOT** | Use `ReservationState` for operational queries (is guest in property?) |
| **Migration Impact** | Single migration: add `checked_in_at`, `checked_out_at`, `completed_at` columns to `property_reservations` — nullable, default null |
| **Risk** | Low: existing queries on `reservation_state` are unaffected |
| **Ghost Binding Interaction** | None — financial settlement uses `completed_at` as trigger, not enum |

---

## Q3 — Operational Task Model

### Analysis

**Current state:**
- `Gorev` model: `baslangic_tarihi`, `bitis_tarihi`, `atanan_user_id`, `gorev_durumu`, `gorev_tipi` (static list: `musteri_takibi`, `ilan_hazirlama`, `musteri_ziyareti`, `dokuman_hazirlama`, `diger`)
- `GorevCreated` event → `NotifyN8nOnGorevCreated` listener → n8n webhook → Telegram/WhatsApp/Email
- n8n pipeline already handles `GorevDurumChanged`, `GorevDeadlineYaklasiyor`, `GorevGecikti`
- **Missing:** `ilan_id` (property), `reservation_id` (reservation), operational task types
- **SAAB principle:** New subsystem only if existing structure cannot carry the business requirement

**Can Gorev carry operational tasks?**

Required for Check-in/out Wave 1:
1. `ilan_id` — property FK (MISSING, but trivially addable to `$fillable` + migration)
2. `reservation_id` — reservation FK (MISSING, but trivially addable)
3. Task types: `temizlik` (cleaning), `hazirlik` (pre-arrival readiness), `kontrol` (inspection), `havuz_bakim` (pool), `bahce_bakim` (garden) — not in static list, but the static list is a convenience method, not a DB constraint
4. Idempotency: prevent duplicate tasks for the same reservation — `scopeByIdempotencyKey` with `reservation_id + task_type`
5. Staff assignment: `atanan_user_id` already exists — cleaner = User in the system

**Option A — Extend Gorev (recommended):**
Gorev already has: deadline tracking, priority, status, assignment, n8n notification pipeline, API controller. Extension is:
- Migration: add `ilan_id`, `reservation_id`, extend task types
- Idempotency: add unique constraint on `(reservation_id, task_type)` per type
- No new model, service, event, or infrastructure needed
- n8n already sends to Telegram/WhatsApp — cleaner receives notification automatically

**Option B — New CleaningTask model:**
Creates a new domain model. Requires: new service, new controller, new event, new n8n wiring. Justified only if Gorev's fields and patterns genuinely cannot model cleaning tasks. They can.

**Option C — New PropertyOperationTask:**
Over-engineered for Wave 1. Creates parallel task infrastructure with no immediate benefit.

**Option A Risk — Semantic pollution:**
Gorev is used for CRM tasks (musteri_takibi, ilan_hazirlama). Mixing operational field tasks (cleaning, pool) with CRM tasks in the same model may confuse `oncelik` (priority) and `gorev_tipi` taxonomy.

**Counter-argument:** The taxonomy is already extensible. The static `getTipler()` is a convenience, not a constraint. The operational types can be added to the static list. The priority system (`acil`, `yuksek`, `normal`, `dusuk`) maps naturally: cleaning before check-in = `yuksek`, routine garden = `normal`.

**Option A Risk — GorevService GuardsAgentWrites:**
`GorevService` has `GuardsAgentWrites` trait blocking AI agent writes on `atamaYap`, `update`, `destroy`. This is appropriate for CRM tasks (agent should not auto-assign or complete CRM work). For operational tasks, the AI agent *should* create tasks automatically. This requires a separate service or a conditional guard.

**Mitigation:** Create a new `OperationalGorevService` that does NOT use `GuardsAgentWrites` and is specifically for operational task automation. GorevService remains for manual CRM task management.

### Decision

**APPROVED: Option A — Extend Gorev with OperationalGorevService**

---

## CHECKOUT-D2 — Task Authority

| Field | Value |
|-------|-------|
| **Approved Option** | A — Extend Gorev model + OperationalGorevService |
| **Rationale** | Gorev has the complete infrastructure: deadline tracking, assignment, n8n webhook pipeline, status transitions, scopes. Extension is minimal: two FK columns + idempotency unique constraint + operational task types. OperationalGorevService separates AI-triggered automation from manual CRM task management. |
| **MUST** | Create `OperationalGorevService` (no `GuardsAgentWrites`) for automated task creation |
| **MUST** | Add idempotency unique constraint: `(reservation_id, task_type)` prevents duplicate tasks |
| **MUST** | Task types: `hazirlik` (pre-arrival), `temizlik` (post-checkout turnover), `kontrol` (inspection), `havuz` (pool), `bahce` (garden) |
| **MUST** | `baslangic_tarihi` = `start_date - 1 day` (pre-arrival), `bitis_tarihi` = `start_date - 2 hours` (deadline before check-in) |
| **MUST NOT** | Use `GorevService` for automated operational task creation (blocked by `GuardsAgentWrites`) |
| **MUST NOT** | Create a new task model for cleaning or turnover |
| **Migration Impact** | Migration: add `ilan_id` (FK nullable), `reservation_id` (FK nullable) to `gorevler` table. Add unique index on `(reservation_id, task_type)`. Add operational task types to static enum. |
| **Risk** | Low: minimal schema change, GorevController/ApiController unchanged, n8n pipeline unchanged |
| **Reuse** | `GorevCreated` → `NotifyN8nOnGorevCreated` → n8n → Telegram/WhatsApp — already wired |

---

## Q6 — Financial Settlement Boundary

### Analysis

**Current state:**
- `ReservationCompletionLedgerService` is registered as a singleton in `AppServiceProvider:177-178`:
  ```php
  $this->app->singleton(
      \App\Contracts\Finance\ReservationCompletionLedgerContract::class,
      \App\Services\Finance\ReservationCompletionLedgerService::class
  );
  ```
- **The service file does not exist.** It is a ghost binding.
- `ReservationCompletedEvent` docblock says it should trigger financial closure, but the event is never dispatched (no `ReservationCompletionJob`).
- The financial ledger system (`FinancialLedgerService`, `LedgerEntry`) exists and is working for other domains.
- Commission calculator (`CommissionCalculator`) exists but is not called at checkout.

**Option A — Bind to checkout/completion operation:**
Create the `ReservationCompletionLedgerService` file, wire it to `ReservationCompletedEvent`. Trigger: `completed_at` timestamp set (or `end_date` passed).

**Risk of Option A:** The service file doesn't exist. Building it requires understanding the complete financial model: which ledger entries, which accounts, which amounts. This is a non-trivial domain modeling effort that is not in scope for Check-in/out Wave 1.

**Option B — Bind to property readiness:**
Financial settlement after cleaner marks property ready and manager approves.

**Risk of Option B:** This introduces a circular dependency: cleaner task → completion → financial settlement. But the cleaner task is itself triggered by checkout. Settlement cannot precede turnover. This creates a long causal chain where financial settlement depends on cleaner workflow completion.

**Option C — Separate Financial Closure entirely (recommended):**
Financial Closure capability has its own lifecycle. It is not Check-in/out Wave 1. The `ReservationCompletionLedgerService` ghost binding is a **pre-existing design intent**, not a commitment to Wave 1. The financial model requires its own discovery, SAAB decision, and implementation.

The ghost binding is a **documentation debt**, not a Wave 1 requirement. Its existence does not obligate implementation in this wave.

### Ghost Binding Verdict

> **The absence of the service file is evidence of non-existence, not deferred implementation.**

`AppServiceProvider:174` comment says "Production: ReservationCompletionLedgerService records 3 LedgerEntries on completion." This is a **design sketch**, not an approved SAAB decision. It has no SAAB decision record, no checksum, no evidence of board approval. It should be removed from the container binding or marked as `TODO-SAAB-D6`.

**Noting for governance:** The ghost binding itself is a SAB Rule 3 violation (no ghost implementation). This is logged as a documentation/architecture debt, not blocking Wave 1.

### Decision

**APPROVED: Option C — Financial Closure is its own capability**

---

## CHECKOUT-D3 — Financial Settlement Boundary

| Field | Value |
|-------|-------|
| **Approved Option** | C — Financial Closure is separate capability |
| **Rationale** | `ReservationCompletionLedgerService` is a ghost binding (file does not exist). Building financial settlement requires its own domain discovery: which ledger accounts, which amounts, which currency conversions, which owner payout rules. This is a separate capability with its own SAAB charter. Wave 1 does not include financial settlement. |
| **MUST** | Financial Closure gets its own SAAB discovery phase before implementation |
| **MUST** | Remove or annotate the ghost binding in `AppServiceProvider:177-178` as `// @sab-ghost-binding — SAAB D6 pending` |
| **MUST NOT** | Implement `ReservationCompletionLedgerService` in Wave 1 |
| **MUST NOT** | Treat the AppServiceProvider comment as a binding commitment |
| **Ghost Binding Debt** | `CERT-DEBT-D6-01`: `ReservationCompletionLedgerService` ghost binding — service file missing. Logged, non-blocking for Wave 1. |
| **Migration Impact** | None for Wave 1 |
| **Risk** | Low: financial settlement remains manual for now. No regression. |

---

## Other Questions — Non-Blocking Decisions

These questions are resolved by existing architecture and do not require further SAAB action:

| Q | Decision | Basis |
|---|----------|-------|
| Q2 — Check-in/out time canonical | **Ilan level** (`check_in_time`/`check_out_time` on `Ilan`, not reservation) | Config default 14:00/11:00. Property-level default is correct. |
| Q4 — Completion trigger | **Scheduled command** (daily, `end_date <= today` query) | Laravel scheduler is the natural fit. Queue-based job dispatched by scheduler. |
| Q5 — Access code delivery | **Internal** (`PropertyAccessAsset.tanimlayici_no` → WhatsApp/Email via `NotificationDispatcher`) | `PropertyAccessAsset` exists (Sprint 12D). No external platform integration in Wave 1. |
| Q7 — Idempotency granularity | **`reservation_id + task_type`** | Supports modification (if dates change, task is replaced). Simpler than date-qualified. |
| Q8 — Legacy YazlikRezervasyon | **PropertyReservation only** | Discovery confirms canonical path is `PropertyReservation`. `YazlikRezervasyon` is a separate system (separate table, separate state machine). Wave 1 does not touch legacy. |

---

## Minimum Viable Automation — Wave 1 Scope

### Goal
> When a reservation arrives, YALIHAN prepares the readiness task **without human intervention**.

**Success criterion:** A confirmed `PropertyReservation` triggers an automated `Gorev` for the cleaner, delivered via n8n to Telegram/WhatsApp, before the guest checks in.

### Wave 1 Components

**Canonical Event (already exists, wire it):**
- `ReservationCreatedEvent` → `ProcessReservationCreated` (already exists, add one method)

**New Job:**
- `CreateOperationalTasksJob` — dispatched from `ProcessReservationCreated`
  - Creates `Gorev` for `hazirlik` (pre-arrival readiness) with `reservation_id`, `ilan_id`
  - Deadline: `start_date - 2 hours` (must be done before check-in time)
  - Priority: `yuksek`
  - Idempotency: unique constraint prevents duplicate task for same reservation
  - Dispatches `GorevCreated` → n8n → Telegram/WhatsApp to assigned staff

**New Service:**
- `OperationalGorevService` — no `GuardsAgentWrites`, for AI-triggered automation only
  - `createPreArrivalTask()`: creates `hazirlik` Gorev
  - `createTurnoverTask()`: creates `temizlik` Gorev (for Wave 2)

**New Migration:**
- `gorevler`: add `ilan_id` (FK nullable), `reservation_id` (FK nullable)
- Unique index: `(reservation_id, task_type)` — idempotency
- `gorevler` seed: add operational task types to `getTipler()` static method

**Scheduled Command (Q4):**
- `reservation:complete` — daily, checks `end_date <= today` AND `completed_at IS NULL`
- Dispatches `ReservationCompletedEvent` (currently stub, now wired to listener)
- Listener: creates turnover `Gorev` (`temizlik`) with deadline `end_date + same day`

**Guest Pre-Arrival Communication (Wave 2 deferred):**
- `ProcessReservationCreated`: add pre-arrival instruction dispatch — deferred to Wave 2

**What is NOT in Wave 1:**
- ❌ `ReservationCompletedEvent` financial wiring
- ❌ `ReservationCompletionLedgerService` implementation
- ❌ Smart lock / access code delivery
- ❌ Breezeway / Oper / Hosty integration
- ❌ Damage inspection workflow
- ❌ Pool/garden service tasks
- ❌ `checked_in_at` / `checked_out_at` timestamps
- ❌ `YazlikRezervasyon` support

### Wave 1 File Impact (Minimal)

| File | Action |
|------|--------|
| `app/Jobs/Reservation/CreateOperationalTasksJob.php` | NEW |
| `app/Services/Reservation/OperationalGorevService.php` | NEW |
| `database/migrations/YYYY_MM_DD_HHMMSS_add_operational_fields_to_gorevler.php` | NEW |
| `app/Console/Commands/ReservationCompleteCommand.php` | NEW |
| `app/Providers/EventServiceProvider.php` | UPDATE — wire ReservationCompletedEvent listener |
| `app/Modules/TakimYonetimi/Models/Gorev.php` | UPDATE — add `ilan_id`, `reservation_id` to fillable, scopes |
| `app/Jobs/Reservation/ProcessReservationCreated.php` | UPDATE — dispatch CreateOperationalTasksJob |
| `docs/SAB.md` | UPDATE — CHECKOUT-D1, CHECKOUT-D2, CHECKOUT-D3 normative records |

**Total new files: 5. Total modified files: 3. Zero breaking changes.**

---

## Implementation Authorization

| Decision | Status |
|----------|--------|
| **IMPLEMENTATION AUTHORIZED** | ✅ YES |

**Authorization:** SAAB Architecture Board — Claude Opus 4.8

**Authorized Implementation Target:** Kilo Code + Claude Sonnet 4.6 — Check-in/out Wave 1

**Authorized Scope:** As defined in "Minimum Viable Automation — Wave 1 Scope" above.

**Success criterion:** A confirmed `PropertyReservation` triggers an automated `Gorev` for cleaner, delivered via n8n to Telegram/WhatsApp, before the guest checks in.

---

## Governance

| Item | Value |
|------|-------|
| Decision | CHECKOUT-D1, CHECKOUT-D2, CHECKOUT-D3 |
| Authority | SAAB Architecture Board v11.1 |
| Board Model | Claude Opus 4.8 |
| Baseline | `fd70ecc` |
| Discovery | Kilo Code (Claude Sonnet 4.6) — `fd70ecc` |
| Advisory | Gemini 3.7 Flash — adversarial review |
| Ghost Binding Debt | `CERT-DEBT-D6-01` — logged, non-blocking |
| Decision Type | Normative — binding for implementation |
| SAAB Charter | Required for Financial Closure (separate capability) |
| Next | Kilo Code + Sonnet 4.6 → Wave 1 Implementation |
