# SAAB Board Resolution — BR-20260724-LIFE-CYCLE-BASELINE
**Strategic Architecture & Automation Board (SAAB)**  
**Date:** 2026-07-24T18:15:11+03:00  
**Status:** RATIFIED & APPROVED  

---

## 1. Revised Lifecycle Analysis

The board replaces all previous qualitative estimates of manual completion with the following verified analysis:

| Stage | Bounded Context | State of Automation | SAAB Directive |
|---|---|---|---|
| **Owner Acquisition** | CRM / Owner | `MANUAL` | Document as legal workflow; implement after P0. |
| **Property Onboarding** | Property Core | `PARTIAL` | Automated folder scaffold exists; forms require manual entry. |
| **Media & Content** | Media | `PARTIAL` | Description generation is automated; photography/sorting is manual. |
| **Commercial Offering** | Commercial Offering | `MANUAL` (Legacy) | Exclude pricing from Listing; establish independent offering models. |
| **Channel Publishing** | Listing & Publishing | `MANUAL` (Mock) | Code audit shows stubs; defer real integrations until P0/P1 are certified. |
| **Reservation & Sync** | Reservations | `MANUAL` (Legacy) | Canonical reservation models and conflict detection are prerequisites. |
| **Field Operations** | Operations | `MANUAL` | Link cleanup and check-in tasks to reservation lifecycle events. |
| **Finance Reconciliation** | Finance | `PARTIAL` | Double-entry ledger is automated; payouts/bank mutabakat is manual. |

---

## 2. Capability Prioritization Metric

To prevent scope creep and ensure focus on high-impact business automation, all new capabilities must be prioritized using the following formula:

$$\text{Priority Score} = \frac{\text{Manual Time} \times \text{Frequency} \times \text{Error Risk} \times \text{Revenue Impact}}{\text{Implementation Risk}}$$

No capability may transition from `TARGET` to `Proposed` or `Active` without a calculated Priority Score verified against a Business Automation Index (BAI) baseline.

---

## 3. Mock Service Integration Audit

The code audit of `CalendarSyncService.php` confirms:
*   The class is called in active production paths via `CalendarSyncController` and the scheduled `calendar:sync` artisan command.
*   The adapter sync logic (e.g., `pushToAirbnb`, `pushToBookingCom`) returns `['success' => true]` stubs.
*   **Directive:** Directly expanding these mocks into real API adapters is classified as **`RISKY`**. They must remain deferred (`DEFER`) until the core domain primitives (Property Identity, Commercial Offering, and Canonical Reservation) are certified in production.

---

## 4. Phase Backlog Program

### Phase P0: Domain & Data Reality (Prerequisites)
1.  Canonical Property Identity
2.  Commercial Offering models
3.  Channel Listing mappings
4.  Canonical Reservation structure
5.  Availability Calendar and Conflict Detection

### Phase P1: Internal Automation Loop (Direct Bookings)
```
Reservation Created ──> Availability Blocked ──> Cleaning Task Scheduled ──> Payout Projected
```

### Phase P2: External Integrations (Adapters)
1.  Airbnb API Adapter
2.  Booking.com API Adapter
3.  Automated Bank/Platform Reconciliation
