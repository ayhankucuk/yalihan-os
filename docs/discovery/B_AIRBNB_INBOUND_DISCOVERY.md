# SAAB v8 — Discovery Report: Airbnb Inbound Completion

**Document ID:** `B_AIRBNB_INBOUND_DISCOVERY`  
**Capability:** Airbnb Inbound Reservation Lifecycle  
**Baseline Commit:** `a97ac13`  
**Role:** Research Office / Repository-wide Discovery  
**Mode:** READ-ONLY EVIDENCE AUDIT  

---

## 1. EXECUTIVE VERDICT

### Is a new Airbnb integration or architecture required?
**NO.** The repository already contains an enterprise-grade, multi-channel inbound ingestion pipeline via **Channex** (`ChannexWebhookController`, `ChannexRevisionProcessor`, `ChannexReservationIngestService`, `ChannexRevisionsRecoveryJob`) which converges directly on the canonical `ReservationService` (`createReservation`, `modifyReservation`, `cancelReservation`).

### Core Finding
Airbnb reservations **already enter YALIHAN OS as first-class canonical reservations** when routed through Channex (`external_channel = 'airbnb'`). When an Airbnb webhook or revision feed item is received:
1. It is normalized into `ChannexReservationPayload` (with `channel: 'airbnb'`).
2. It calls canonical `ReservationService::createReservation()` / `modifyReservation()` / `cancelReservation()`.
3. It creates/updates/cancels `PropertyReservation` and locks/releases `PropertyAvailability` atomically.
4. It dispatches canonical events (`ReservationCreatedEvent`, `ReservationModifiedEvent`, `ReservationCancelledEvent`).
5. It automatically triggers all downstream automation:
   - **Availability:** `ProcessReservationCreated` / `AvailabilitySynchronizationService`
   - **Finance:** `FinancialLedgerService`
   - **Operations:** `CreateOperationalTasksJob`
   - **Guest Communication (Wave 1):** `SendGuestConfirmationJob`
   - **Cancellation Communication (Wave 2):** `ListenCancellationCommunication` → `SendCancellationNotificationJob`
6. Explicit ACK is returned to Channex only after local DB transaction commit.

### Strategic Recommendation
**Do NOT write a standalone Airbnb adapter or parallel ingestion path.** Direct Airbnb API connection is blocked (no direct partner API credentials; existing `AirbnbClient` is a calendar-push stub). **Channex is the established, certified, and canonical transport provider for Airbnb.**

The scope for **B — Airbnb Inbound Completion** is strictly verification, minor gap closure (financial amount stamping, explicit Airbnb end-to-end test suite), and zero architectural additions.

---

## 2. CURRENT AIRBNB PATH

### Repository Inventory

| Component | Class / Location | Role & Status |
|-----------|------------------|---------------|
| **Inbound Webhook** | `App\Http\Controllers\Api\ChannexWebhookController` | **Active.** Receives Airbnb webhooks via Channex (`POST /api/v1/webhook/channex`). |
| **Recovery Poller** | `App\Jobs\ChannelManager\ChannexRevisionsRecoveryJob` | **Active.** 15-min scheduled poller for missed Airbnb/OTA revisions feed. |
| **Revision Processor** | `App\Services\ChannelManager\ChannexRevisionProcessor` | **Active.** Single SSOT processor for CREATE, MODIFY, CANCEL with explicit ACK. |
| **Ingest Service** | `App\Services\ChannelManager\ChannexReservationIngestService` | **Active.** Bridges payload to canonical `ReservationService`. |
| **Reservation DTO** | `App\DTOs\ChannelManager\ChannexReservationPayload` | **Active.** Normalizes JSON:API and flat Airbnb/Channex payloads. |
| **Tenant / Listing Resolver** | `App\Services\ChannelManager\ChannexWebhookTenantResolver` | **Active.** Resolves tenant and `ilan_id` via `ilan_takvim_sync`. |
| **Listing Mapping** | `App\Models\IlanTakvimSync` (`platform = 'airbnb'`) | **Active.** Maps `property_id` ↔ `external_listing_id`. |
| **Outbound Availability Adapter** | `App\Infrastructure\ChannelManager\Adapters\AirbnbChannelAdapter` | **Active.** Implements `ChannelSyncContract` (pushes availability to Airbnb via Channex). |
| **Outbound Airbnb Client** | `App\Infrastructure\ChannelManager\Airbnb\AirbnbClient` | **Stub/Blocked.** Outbound calendar entries API (`POST /v2/calendar_entries`) for direct Airbnb. Unused in production. |
| **Legacy iCal Feed Job** | `App\Jobs\SyncPropertyCalendarFeedJob` | **Legacy/Auxiliary.** iCal polling for calendar blocking (`source_system = 'airbnb_ical'`). Does NOT create canonical reservations. |

### Inbound Arrival Flow
Airbnb inbound reservations arrive via **Option B: Through Channex**:
```
Airbnb Guest Booking
        ↓
Airbnb OTA Platform
        ↓
Channex Multi-Channel Aggregator
        ↓ (Webhook POST /api/v1/webhook/channex OR Revisions Feed Polling)
ChannexWebhookController / ChannexRevisionsRecoveryJob
        ↓
ChannexRevisionProcessor
        ↓
ChannexReservationIngestService
        ↓
Canonical ReservationService
```

---

## 3. CANONICAL RESERVATION BOUNDARY

There is **exactly one** approved canonical reservation execution path in YALIHAN OS:

```
External Payload (Airbnb via Channex)
        ↓
Normalization (ChannexReservationPayload: action, dates, guest, channel='airbnb')
        ↓
Property & Tenant Resolution (ChannexWebhookTenantResolver via ilan_takvim_sync)
        ↓
Idempotency Guard (external_reservation_id + external_channel + tenant_id)
        ↓
Canonical ReservationService
        ├─ DB Transaction
        ├─ Overlap Conflict Check (PropertyReservation + PropertyAvailability lock)
        ├─ PropertyReservation::create()
        └─ PropertyAvailability::update(is_available = false, reservation_id)
        ↓ [AFTER DB COMMIT]
Canonical Lifecycle Events Dispatch
        ├─ ReservationCreatedEvent
        ├─ ReservationModifiedEvent
        └─ ReservationCancelledEvent
        ↓
Downstream Autonomous Consumers (Unmodified)
        ├─ Availability: AvailabilitySynchronizationService (broadcasts to other channels)
        ├─ Finance: FinancialLedgerService
        ├─ Operations: CreateOperationalTasksJob
        ├─ Guest Confirmation: SendGuestConfirmationJob
        └─ Guest Cancellation: ListenCancellationCommunication → SendCancellationNotificationJob
        ↓
Explicit External ACK (ChannexBookingAcknowledger::acknowledgeRevision)
```

---

## 4. CREATE / MODIFY / CANCEL PARITY MATRIX

| Feature / Step | Channex Inbound (Airbnb) | Booking.com Inbound | Canonical Reservation Service |
|----------------|--------------------------|---------------------|-------------------------------|
| **Tenant Resolution** | `ChannexWebhookTenantResolver` via `ilan_takvim_sync` | `BookingPropertyResolver` via `ilan_takvim_sync` | Scoped via `Ilan->tenant_id` |
| **Property Resolution** | `external_listing_id` → `ilan_id` | `hotel_code` → `ilan_id` | `property_id` |
| **Idempotency Key** | `external_reservation_id` + `external_channel` + `tenant_id` | `external_reservation_id` + `external_channel` + `tenant_id` | `id` / `external_reservation_id` |
| **Duplicate Delivery** | Returns existing `PropertyReservation`, safe 200/ACK | Returns existing `PropertyReservation`, safe ACK | Returns existing |
| **Date Normalization** | ISO `Y-m-d` via `DateTimeImmutable` | ISO `Y-m-d` via `toCanonicalGuestData()` | `Carbon::parse()->startOfDay()` |
| **Guest Normalization** | Name, Phone, Email, Adult count | Name, Phone, Email, Adult count | Formatted guest array |
| **Availability Lock** | Done by `ReservationService` (`lockForUpdate`) | Direct DB insert (`lockForUpdate` manual) | Canonical `lockForUpdate` on `PropertyAvailability` |
| **Conflict Handling** | Rejects with `ChannexReservationRejectedEvent` | Rejects with `BookingReservationRejectedEvent` | Throws `Exception("Conflict detected")` |
| **Transaction Boundary** | `DB::transaction` inside `ReservationService` | `DB::transaction` manual inside service | `DB::transaction` |
| **ACK Invariant** | ACK **only after** DB commit succeeds | ACK **only after** DB commit succeeds | N/A (Internal) |
| **ACK Failure Handling** | Reservation stays committed (no rollback) | Reservation stays committed (no rollback) | N/A |
| **Canonical Event** | `ReservationCreatedEvent` | ⚠️ Missing in Booking CREATE (only channel event) | Dispatches `ReservationCreatedEvent` |
| **Modification Path** | `ReservationService::modifyReservation()` | `ReservationService::modifyReservation()` | Canonical modify + conflict check |
| **Cancellation Path** | `ReservationService::cancelReservation()` | `ReservationService::cancelReservation()` | Canonical cancel + availability release |
| **Terminal State Check** | Cancelled reservations ignore modifications | Cancelled reservations ignore modifications | ADR-008 enforced |
| **Downstream Automation** | Availability, Finance, Ops, Guest Comms | Manual or Event-based | Automatically triggered |

---

## 5. REUSABLE COMPONENTS

The following existing components are 100% reusable for Airbnb inbound:

1. **`App\Http\Controllers\Api\ChannexWebhookController`** — Fully functional webhook endpoint with HMAC signature verification, tenant resolution, idempotency, and async job dispatch.
2. **`App\Services\ChannelManager\ChannexRevisionProcessor`** — Single processor handling CREATE, MODIFY, CANCEL with explicit post-commit ACK.
3. **`App\Services\ChannelManager\ChannexReservationIngestService`** — Thin service calling canonical `ReservationService`.
4. **`App\Jobs\ChannelManager\ChannexRevisionsRecoveryJob`** — 15-minute polling recovery job ensuring zero dropped reservations even if webhooks fail.
5. **`App\DTOs\ChannelManager\ChannexReservationPayload`** — Normalizes Airbnb webhook payloads (both JSON:API revisions and flat webhook formats).
6. **`App\Services\ReservationService`** — Canonical core (`createReservation`, `modifyReservation`, `cancelReservation`, `createReservationWithOverride`).
7. **Downstream Event Listeners:**
   - `ListenReservationCreated` → `ProcessReservationCreated`
   - `ListenReservationCreatedReadiness`
   - `SendGuestConfirmationJob`
   - `CreateOperationalTasksJob`
   - `ListenCancellationCommunication` → `SendCancellationNotificationJob`

---

## 6. TRUE GAPS CLASSIFICATION

| Item | Classification | Description & Remediation |
|------|----------------|---------------------------|
| **Inbound Webhook Routing** | `EXISTS_REUSABLE` | Fully implemented in `ChannexWebhookController`. |
| **Revisions Polling Recovery** | `EXISTS_REUSABLE` | Fully implemented in `ChannexRevisionsRecoveryJob`. |
| **Canonical Event Integration** | `EXISTS_REUSABLE` | `ReservationCreatedEvent` and `ReservationCancelledEvent` are dispatched. |
| **Financial Fields Stamping** | `PARTIAL` | `ChannexReservationIngestService` stamps `external_reservation_id` and `external_channel`, but does not stamp `islem_tutari` (`totalPrice`) and `currency` on `PropertyReservation` during `ingest()`. *(Minor 3-line update).* |
| **Dedicated Airbnb Feature Test** | `MISSING` | While `ChannelManagerWave2Test`, `ChannelManagerWave3Test`, and `ChannexReliabilityRecoveryB1RTest` use `'airbnb'` as channel fixture, there is no dedicated `AirbnbInboundLifecycleTest` confirming full end-to-end trace from Airbnb webhook to Wave 1 / Wave 2 communications. |
| **Direct Airbnb Inbound Webhook** | `PROVIDER_LIMITATION` | Direct Airbnb API partner access is unavailable without certified partner credentials; Channex aggregator is the official gateway. |

---

## 7. DEAD / LEGACY CODE

| Component | Status | Recommendation |
|-----------|--------|----------------|
| `App\Infrastructure\ChannelManager\Airbnb\AirbnbClient` | `DEAD_CODE` / `STUB` | Kept for future direct partner capability; do not modify or route inbound traffic here. |
| `App\Jobs\SyncPropertyCalendarFeedJob` (with `source_system = 'airbnb_ical'`) | `LEGACY` | Used only for fallback iCal busy blocks when no Channex connection exists. Does not conflict with canonical reservations. |

---

## 8. IDEMPOTENCY MODEL

### Canonical Ingestion Key
```
{tenant_id}:{external_channel}:{external_reservation_id}
```
Example: `1:airbnb:HM4PK9Q2`

### Deduplication Proof
1. **Webhook Layer:** `ChannexWebhookController` checks `PropertyReservation::where('external_reservation_id', $id)->where('tenant_id', $tenantId)->exists()`. If `action === 'new'` and record exists, returns HTTP 200 `already_processed` immediately.
2. **Ingest Service Layer:** `ChannexReservationIngestService::ingest()` checks `external_reservation_id` + `external_channel` + `tenant_id`. If found, returns existing `PropertyReservation` without re-inserting or re-locking availability.
3. **Database Layer:** Atomic `DB::transaction` with `lockForUpdate` prevents race conditions between simultaneous webhook calls.
4. **Downstream Job Layer:** `SynchronizeAvailabilityJob` and `SendGuestConfirmationJob` have their own idempotency keys (`findExistingSync` and `reservation_confirmations` audit table).

---

## 9. FAILURE / RECOVERY MODEL

| Scenario | Behavior & Guarantee |
|----------|----------------------|
| **Unknown Property / Listing** | `ChannexWebhookTenantResolver` returns `null`. Webhook responds HTTP 200 `unknown_property` (prevents endless OTA webhook retries); dispatches `ChannexReservationRejectedEvent` for audit. |
| **Duplicate Reservation** | Idempotently returns existing record; responds HTTP 200 `already_processed`. Safe explicit ACK sent to Channex. |
| **Out-of-Order Modification** | If modification arrives for an already `cancelled` reservation, `ReservationService::modifyReservation` returns existing cancelled model without mutating dates (ADR-008 terminal state invariant). |
| **Out-of-Order Cancellation** | If cancellation arrives for an unknown reservation, returns `null` safely (no-op). If already cancelled, idempotent early return. |
| **Provider Timeout / 5xx** | Queued jobs (`ChannexReservationIngestJob`, `ChannexReservationModifyJob`, `ChannexReservationCancelJob`) have `$tries = 3` and `$backoff = 30`. Missed webhooks are caught within 15 minutes by `ChannexRevisionsRecoveryJob`. |
| **ACK Failure** | If Channex API fails during ACK (`ChannexAcknowledgementException`), the local `PropertyReservation` remains committed (NO ROLLBACK). Subsequent poller runs will retry the ACK safely. |

---

## 10. MINIMUM IMPLEMENTATION SCOPE

To achieve 100% completion of **B — Airbnb Inbound Completion**:

1. **`ChannexReservationIngestService` Update (Minor):**
   - Stamp `islem_tutari` (`$payload->totalPrice`) and `currency` (`$payload->currency`) when creating a reservation, ensuring financial ledger and confirmation notifications receive exact currency amounts.
2. **Test Suite Addition (Clean Verification):**
   - Create `tests/Feature/ChannelManager/Airbnb/AirbnbInboundLifecycleTest.php` covering:
     - Full trace: Airbnb Webhook (CREATE) → `PropertyReservation` (`external_channel = 'airbnb'`) → `ReservationCreatedEvent` → Availability Blocked + Task Created + Guest Confirmation Queued.
     - Airbnb Webhook (MODIFY) → Dates Updated → `ReservationModifiedEvent`.
     - Airbnb Webhook (CANCEL) → `ReservationCancelledEvent` → Availability Released + Cancellation Notification Queued.
     - Idempotency on duplicate Airbnb webhook.
     - Out-of-order modification on cancelled Airbnb reservation.
     - Tenant isolation enforcement on Airbnb listing mapping.

---

## 11. TEST MATRIX (Target for B Implementation)

| Test ID | Test Name | Target Invariant |
|---------|-----------|------------------|
| `B-01` | `airbnb_webhook_creates_canonical_reservation` | Stamped with `external_channel='airbnb'`, `ReservationCreatedEvent` dispatched. |
| `B-02` | `airbnb_webhook_stamps_financial_amount_and_currency` | `islem_tutari` and `currency` populated from Airbnb payload. |
| `B-03` | `airbnb_webhook_is_idempotent_on_duplicate_delivery` | Duplicate delivery returns existing reservation; no duplicate rows created. |
| `B-04` | `airbnb_webhook_modifies_dates_via_canonical_service` | Modification updates start/end dates and dispatches `ReservationModifiedEvent`. |
| `B-05` | `airbnb_webhook_cancels_reservation_and_releases_availability` | Cancellation releases `PropertyAvailability` and dispatches `ReservationCancelledEvent`. |
| `B-06` | `airbnb_cancellation_triggers_guest_cancellation_communication` | `ReservationCancelledEvent` queues `SendCancellationNotificationJob` (Wave 2 pipeline). |
| `B-07` | `airbnb_modification_on_cancelled_reservation_is_ignored` | Terminal state integrity (ADR-008). |
| `B-08` | `airbnb_unknown_listing_is_rejected_without_exception` | HTTP 200 with `unknown_property` reason and rejection audit event. |
| `B-09` | `airbnb_cross_tenant_listing_ingest_is_blocked` | Multi-tenant isolation boundary enforced. |
| `B-10` | `airbnb_ack_failure_does_not_rollback_canonical_reservation` | Commit -> ACK decoupling invariant. |

---

## 12. RISKS

1. **Channel Name Normalization:** Channex payloads might send `channel_name` as `'Airbnb'`, `'airbnb'`, or `'airbnb_official'`. `ChannexReservationPayload` handles this with `strtolower()`, but tests must verify case-insensitivity.
2. **Availability Double-Push:** Receiving an Airbnb reservation locks local availability and triggers `AvailabilitySynchronizationService`. This broadcasts the block to Booking.com and other OTAs, but should NOT cause an infinite loop back to Airbnb. Evidence confirms `AvailabilitySynchronizationService` uses `ChannelSyncExecution` idempotency keys to prevent loops.

---

## 13. RECOMMENDED WAVES

- **Wave B1: Ingest Financial Stamping & Channel Normalization**
  - Add `islem_tutari` & `currency` stamping in `ChannexReservationIngestService`.
- **Wave B2: End-to-End Airbnb Integration & Lifecycle Certification**
  - Implement `tests/Feature/ChannelManager/Airbnb/AirbnbInboundLifecycleTest.php` (10/10 tests).
  - Verify zero regression on existing Channex, Booking, and Guest Communication test suites.

---

## 14. GO / HOLD RECOMMENDATION

### **Verdict: 🟢 GO FOR B IMPLEMENTATION**
- The repository already has the complete architecture in place.
- No new architecture or parallel reservation pipeline is needed.
- Scope is minimal, low-risk, and directly builds upon the certified A1 and A2 foundations.

---

## FINAL QUESTION

> **"What is the smallest implementation required for an Airbnb reservation to complete the existing canonical YALIHAN reservation lifecycle without creating a second reservation architecture?"**

### Answer:
The smallest required implementation is:
1. **Ensure `ChannexReservationIngestService` stamps financial fields (`islem_tutari`, `currency`) on the created `PropertyReservation`** (3 lines in `app/Services/ChannelManager/ChannexReservationIngestService.php`).
2. **Add a comprehensive feature test suite (`AirbnbInboundLifecycleTest.php`)** proving that an inbound Airbnb payload from Channex triggers the canonical `ReservationService` lifecycle (`createReservation` / `modifyReservation` / `cancelReservation`), correctly fires `ReservationCreatedEvent` / `ReservationCancelledEvent`, and drives downstream availability, task creation, and Wave 1 & Wave 2 guest communications without any secondary or parallel write paths.

No new controllers, routes, models, or adapters are required.
