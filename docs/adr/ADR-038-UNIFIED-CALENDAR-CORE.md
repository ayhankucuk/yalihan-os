# ADR-038: Unified Calendar Projection & Segregated Channel Adapter Architecture

* **Status:** RATIFIED (SAAB v11.1 Final Approval)
* **Date:** 2026-07-24
* **Author:** SAAB Enterprise Architecture Board
* **Deciders:** Strategic Architecture & Automation Board (SAAB), Lead Platform Architect

---

## Context and Problem Statement

YALIHAN OS requires multi-channel calendar synchronization across OTA channels (Airbnb, Booking.com, VRBO, iCal feeds) and direct bookings.
Without a clear architectural boundary, channel integrations risk mutating availability states directly or creating redundant canonical representations.

We need to establish:
1. Why Calendar is a **Canonical Calendar Projection (Read Model)** and NOT a canonical aggregate root.
2. Rebuildability and Replay safety guarantees.
3. The distinction between **Availability Engine (Write-Side)** and **Unified Calendar Core (Read-Side)**.
4. Segregated **Channel Adapter Contracts** (`ChannelReservationImporter`, `ChannelAvailabilityPublisher`, `ChannelCalendarReader`).
5. Normalized inbound webhook processing pipeline into **Timeline / Audit**.

---

## Architectural Taxonomy & Layer Hierarchy

```
Property (Physical Aggregate Root)
    │
    ▼
Commercial Offering (Terms & Price Aggregate)
    │
    ▼
Reservation Lifecycle (Booking Aggregate Root: PENDING -> CONFIRMED -> CHECKED_IN -> CHECKED_OUT -> CLOSED)
    │
    ▼
Availability Engine (Write-Side Conflict & Range Locks: property_availability_blocks)
    │
    ▼
Unified Calendar Core (Canonical Calendar Projection & Rebuildable Read Model)
    ├─────────────────────────────────┐
    ▼                                 ▼
iCal / Airbnb Adapter           Booking.com Adapter
    │                                 │
    └────────────────┬────────────────┘
                     ▼
        Raw External Channel Webhook
                     │
                     ▼
          Signature Verification
                     │
                     ▼
           Webhook Inbox Receipt
                     │
                     ▼
             Deduplication
                     │
                     ▼
      Channel Payload Normalization
                     │
                     ▼
             Canonical Command
                     │
                     ▼
     Reservation / Availability Lock
                     │
                     ▼
        Unified Calendar Projection
                     │
                     ▼
             Workspace Timeline
```

---

## Decision Outcomes

### 1. Unified Calendar is a "Canonical Calendar Projection"
*   **Decision:** Calendar is a **read-model projection** compiled from write-side source aggregates (`PropertyReservation`, `PropertyAvailabilityBlock`).
*   **Rebuildability Guarantee:** If the calendar projection store is cleared or dropped, it can be 100% reconstructed by replaying `PropertyReservation`, `PropertyAvailabilityBlock`, and Immutable Domain Events without any data loss.
*   **Rationale:** `PropertyAvailabilityBlock` is the SSOT for physical availability; `PropertyReservation` is the SSOT for booking contracts. Unified Calendar provides a query-optimized projection for cockpits, iCal feeds, and sync endpoints.

### 2. Availability Engine vs. Unified Calendar Core
*   **Availability Engine (Write-Side):**
    - Enforces transactional pessimistic locks (`lockForUpdate()`).
    - Validates date range overlaps using half-open intervals `[starts_at, ends_at)`.
    - Manages `property_availability_blocks` records (`RESERVATION`, `OWNER_BLOCK`, `MAINTENANCE`, `CLEANING`, `OPTION_HOLD`, `MANUAL_BLOCK`).
*   **Unified Calendar Core (Read-Side):**
    - Aggregates multi-channel schedules into unified chronological calendar feeds.
    - Projects availability status, nightly rates, and min/max stay rules per date.
    - Serves iCal feeds, cockpit views, and WebSocket sync updates.

### 3. Segregated Channel Adapter Contracts (Interface Segregation Principle)
To prevent "god interfaces" and support channels with varying capabilities (e.g. iCal read-only vs full OTA API), adapters implement segregated interfaces:

```php
namespace App\Services\ChannelManager\Contracts;

interface ChannelAdapter
{
    public function channelCode(): string;
    public function capabilities(): array;
}

interface ChannelReservationImporter
{
    public function importReservation(array $payload): array;
}

interface ChannelAvailabilityPublisher
{
    public function publishAvailability(int $tenantId, int $propertyId, array $availabilityData): bool;
}

interface ChannelCalendarReader
{
    public function fetchCalendar(int $tenantId, int $propertyId): array;
}
```

### 4. Normalized Inbound Webhook Pipeline & Timeline Isolation
*   **Raw Webhook Receipt & Audit:** Inbound HTTP requests land in `WebhookInbox` with signature verification. Raw HTTP payloads stay strictly in `WebhookInbox` / `Integration Audit` logs and MUST NEVER be written directly to `WorkspaceTimeline`.
*   **Deduplication & Normalization:** Payloads are deduplicated and normalized into canonical DTOs before entering application services.
*   **Business Event & Projection:** Only verified business-level facts (e.g., "Airbnb reservation received", "Dates updated by external channel") dispatch domain events to `HermesEventLog` and project onto `WorkspaceTimeline` with `UNIQUE (tenant_id, source_event_id, projection_type)` replay protection.

---

## Consequences

*   **Positive:** Channel adapters do not touch raw DB models directly; they communicate through `UnifiedCalendarCore` and `ReservationApplicationService`.
*   **Positive:** Adding new OTAs (e.g. iCal Import, VRBO, Expedia) requires only implementing appropriate segregated interfaces.
*   **Governance Compliance:** Complies 100% with SAB Rule 1 (Tenant Isolation) and Thin Controller guidelines.
