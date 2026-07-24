# ADR-038: Unified Calendar Core Read Model & Channel Adapter Architecture

* **Status:** APPROVED (SAAB v11.1 Ratified)
* **Date:** 2026-07-24
* **Author:** SAAB Enterprise Architecture Board
* **Deciders:** Strategic Architecture & Automation Board (SAAB), Lead Platform Architect

---

## Context and Problem Statement

YALIHAN OS requires multi-channel calendar synchronization across OTA channels (Airbnb, Booking.com, VRBO) and direct bookings.
Without a clear architectural boundary, channel integrations risk mutating availability states directly or creating redundant canonical representations.

We need to establish:
1. Why Calendar is a **Read Model / Projection** and NOT a canonical aggregate root.
2. The distinction between **Availability Engine** and **Unified Calendar Core**.
3. The contract for **Channel Adapters** (Airbnb, Booking.com).
4. Event flow from external channels into **Timeline / Audit**.

---

## Architectural Taxonomy & Layer Hierarchy

```
Property (Physical Aggregate Root)
    │
    ▼
Commercial Offering (Terms & Price Aggregate)
    │
    ▼
Reservation Lifecycle (Booking Aggregate Root)
    │
    ▼
Availability Engine (Write-Side Conflict & Range Locks)
    │
    ▼
Unified Calendar Core (Canonical Read Model & Projection)
    ├─────────────────────────┐
    ▼                         ▼
Airbnb Adapter          Booking Adapter
    │                         │
    └───────────┬─────────────┘
                ▼
      External Channel Events
                ▼
          Timeline / Audit
```

---

## Decision Outcomes

### 1. Calendar is NOT Canonical Aggregate Root
*   **Decision:** Calendar is a **projection (read model)** compiled from write-side source aggregates (`PropertyReservation`, `PropertyAvailabilityBlock`).
*   **Rationale:** The canonical source of truth for physical availability is `PropertyAvailabilityBlock`. The canonical source of truth for booking contracts is `PropertyReservation`. Calendar provides a fast, query-optimized read projection for UI cockpits and channel sync endpoints.

### 2. Availability Engine vs. Unified Calendar Core
*   **Availability Engine (Write-Side):**
    - Enforces transactional pessimistic locks (`lockForUpdate()`).
    - Validates date range overlaps using half-open intervals `[starts_at, ends_at)`.
    - Manages `property_availability_blocks` records (`RESERVATION`, `OWNER_BLOCK`, `MAINTENANCE`, `CLEANING`, `OPTION_HOLD`, `MANUAL_BLOCK`).
*   **Unified Calendar Core (Read-Side):**
    - Aggregates multi-channel schedules into unified chronological timeline feeds.
    - Projects availability status, nightly rates, and min/max stay rules per date.
    - Serves iCal feeds and WebSocket sync updates.

### 3. Channel Adapter Contract (`ChannelAdapterInterface`)
External OTA providers (Airbnb, Booking.com, VRBO) must implement a unified interface contract:

```php
namespace App\Services\ChannelManager\Contracts;

use App\Services\ChannelManager\DTOs\CalendarSyncPayload;
use App\Services\ChannelManager\DTOs\ExternalReservationDTO;

interface ChannelAdapterInterface
{
    public function importReservations(int $tenantId, int $propertyId): array;

    public function pushAvailability(int $tenantId, int $propertyId, CalendarSyncPayload $payload): bool;

    public function handleWebhook(array $headers, array $payload): ExternalReservationDTO;
}
```

### 4. External Event Flow into Timeline & Audit
*   All inbound webhooks and sync actions from external channels dispatch `ExternalChannelEvent` to `HermesEventLog`.
*   Timeline listeners project external booking facts onto `WorkspaceTimelineRecord`.
*   Replay safety is enforced via `UNIQUE (tenant_id, source_event_id, projection_type)`.

---

## Consequences

*   **Positive:** Channel adapters do not touch raw DB models directly; they communicate through `UnifiedCalendarCore` and `ReservationApplicationService`.
*   **Positive:** Adding new OTAs (e.g. VRBO, Expedia, Direct Engine) requires only implementing `ChannelAdapterInterface`.
*   **Governance Compliance:** Complies 100% with SAB Rule 1 (Tenant Isolation) and Thin Controller guidelines.
