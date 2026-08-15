# SAAB Discovery Brief — Airbnb Inbound Channel

**Capability:** Airbnb Inbound (Channel Manager Provider Wave 3)
**Baseline:** `5504bc1` (Availability Sync CERTIFIED)
**Instance:** Sprint 13 E03 — Channel Manager Provider
**Discovery:** REQUIRED before implementation
**Gate:** SAAB Decision Gate → Authorization

---

## Context

Availability Sync (E02) is CERTIFIED ✅. The outbound push pipeline is in production:

```
Local Reservation → ReservationCreatedEvent
    → ProcessReservationCreated
        → syncAvailability() (→ AvailabilitySynchronizationService)
            → SynchronizeAvailabilityJob
                → AirbnbChannelAdapter / BookingChannelAdapter
                    → OTA platforms
```

E03 is the **inbound** direction: Airbnb reservation webhook → local `PropertyReservation`.

The existing inbound chain already exists in the codebase but has NOT been certified against the SAAB decisions (4.1–4.6).

---

## Existing Inbound Architecture

```
Airbnb/Channex Webhook
    ↓ POST /api/v1/webhook/channex
ChannexWebhookController
    ↓ (signature verified, tenant resolved, action routed)
ChannexReservationJob/ChannexReservationIngestJob/ChannexReservationCancelJob
    ↓
ChannexReservationIngestService
    ├→ ingest()       → ReservationService::createReservation()
    ├→ ingestModification() → ReservationService::modifyReservation()
    └→ ingestCancellation() → ReservationService::cancelReservation()
    ↓ (each calls ReservationService which dispatches lifecycle event)
ReservationCreatedEvent / ReservationModifiedEvent / ReservationCancelledEvent
    ↓ (after commit)
ProcessReservationCreated / ProcessReservationModified / ProcessReservationCancelled
    ├→ SendGuestConfirmationJob (guest notification)
    └→ AvailabilitySynchronizationService → channel sync job → OTA push
```

---

## Questions That Need SAAB Answers

### Q1: Outbound push FROM availability sync — idempotency with inbound webhook replay

When Airbnb sends a webhook for the same reservation twice (retry, re-delivery), the inbound chain processes it idempotently via `external_reservation_id` uniqueness.

BUT: the outbound push chain already pushed availability BEFORE the Airbnb reservation arrived (from the internal reservation flow). After the Airbnb webhook confirms the reservation, the same availability sync would fire again.

**Risk:** Double-push to other channels (Booking.com) for the same availability block.

**Question:** Does the idempotency key in `ChannelSyncExecution` cover this scenario? Is there a duplicate push risk?

### Q2: Inbound order-of-arrival vs outbound projection state

The outbound `SynchronizeAvailabilityJob` fires immediately after local availability is written. If Airbnb's availability push arrives at the OTA BEFORE the reservation webhook, Airbnb could receive stale availability.

**Question:** Is the current `afterCommit()` timing sufficient, or does the reservation confirmation need a confirmation ACK before availability is pushed to Airbnb?

### Q3: Channel failure on ONE channel — cross-channel consistency

The current `AvailabilitySynchronizationService.syncToChannel()` iterates over adapters and records individual results. If AirbnbChannelAdapter succeeds but BookingChannelAdapter fails (or vice versa), the two channels have inconsistent state.

**Question:** Is the per-channel failure handling correct? Is there a rollback or compensation action needed if ONE channel fails?

### Q4: AvailabilitySynchronizationService.write authority — who writes?

The `AvailabilitySynchronizationService.synchronize()` method writes to `PropertyAvailability` (block/update). But the inbound chain creates a reservation via `ReservationService`, which also writes `PropertyAvailability`.

The chain is:
`ChannexReservationIngestService.ingest()` → `ReservationService.createReservation()` → local `PropertyAvailability` write

Then `ReservationCreatedEvent` → `ProcessReservationCreated` → `syncAvailability()` → `AvailabilitySynchronizationService` → `PropertyAvailability` update + `SynchronizeAvailabilityJob`

**Question:** Is the double-write (ReservationService + AvailabilitySynchronizationService) intentional or redundant? Does AvailabilitySynchronizationService need to write `PropertyAvailability` again for inbound reservations?

### Q5: Airbnb → Channex → internal availability

If Airbnb pushes a reservation via Channex webhook, does this reservation need to be re-synced to OTHER channels (Booking.com, Vrbo)?

Current flow: `ReservationCreatedEvent` → `syncAvailability()` → ALL registered channels. This would push to Booking.com too.

**Question:** Is this the correct behavior? Should inbound from Airbnb trigger outbound to Booking.com? Or should the sync direction be channel-specific?

---

## Discovery Actions

| # | Action | Expected Output |
|---|--------|----------------|
| 1 | Map full inbound chain (webhook → local write → event → outbound push) | Sequence diagram |
| 2 | Analyze idempotency collision: inbound replay vs outbound push | Gap analysis |
| 3 | Audit AvailabilitySynchronizationService write path for inbound use | Confirmed correct or fix needed |
| 4 | Check cross-channel failure handling | Gap analysis |
| 5 | Verify tenant isolation on webhook endpoint | Confirmed |
| 6 | Audit signature verification and tenant resolution | Confirmed or fix |

---

## Proposed SAAB Gates for E03

| Gate | Topic |
|------|-------|
| E3.1 | Canonical Source (inbound: external → local) |
| E3.2 | Inbound Event Contract (what data does webhook capture?) |
| E3.3 | Channel Boundary (inbound does NOT write to PropertyAvailability) |
| E3.4 | Idempotency (webhook replay ≠ double booking) |
| E3.5 | Tenant Isolation (webhook tenant resolution) |
| E3.6 | Retry/Evidence (inbound webhook job retry) |

---

## Recommendation

**Discovery mode: ON.** Do NOT implement before SAAB decision gates are defined.

This brief is the starting point. Next: Kilo Code audits the existing inbound chain, identifies which of the 5 questions are real gaps, and proposes SAAB Decisions E3.1–E3.6.

---

**Board:** SAAB — Yalıhan AI OS Strategic Architecture Board
**Author:** Kilo Code (Agentic)
**Status:** DISCOVERY REQUIRED
**Next:** Airbnb Inbound SAAB Discovery → Decision Gate → Implementation Authorization
