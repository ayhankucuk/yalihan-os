# Yalıhan AI OS — Architecture Specification
**Ratified Charter:** SAAB v11.1 (Governance Frozen)  
**Status:** ACTIVE  
**Last Updated:** 2026-07-24  

---

## 1. Purpose and Scope

This document specifies the current accepted system architecture for **Yalıhan AI OS**, an AI-First Digital Property Intelligence Platform. It defines the logical layers, component responsibilities, data structures, and boundaries. 

The primary objective of this architecture is to automate property management operations and maximize the **Business Automation Index (BAI)**.

---

## 2. Source-of-Truth Statement

This architecture specification is subordinate to the **Strategic Architecture & Automation Board (SAAB)** constitution.

*   **Governing Charter:** [SAB.md](SAB.md)
*   **Rules & Constraints:** [.sab/authority.json](../.sab/authority.json)

In the event of a conflict between this specification and the compiled repository code or tests, the code and tests represent the final truth.

---

## 3. Architectural Status Legend

To prevent future or conceptual designs from being confused with running software, all components in this specification are marked with an explicit status:

*   `IMPLEMENTED`: Tested, certified, and currently running in the codebase.
*   `PARTIAL`: Partially implemented; some components exist in code but require additions.
*   `TARGET`: Purely conceptual or planned; no implementation exists in the current codebase.
*   `LEGACY`: Deprecated pattern or model slated for removal or refactoring.
*   `UNKNOWN`: Unverified state.

---

## 4. System Context

Yalıhan AI OS is designed as a **Modular Monolith** built on Laravel 10 and EIOS. It segment operations into 4 logical layers:

```
┌─────────────────────────────────────────────────────────┐
│                   YALIHAN PLATFORM                      │
├─────────────────────────────────────────────────────────┤
│ Layer 1 — Observation (UI Cockpit / Dashboards)         │
├─────────────────────────────────────────────────────────┤
│ Layer 2 — Execution (Hermes / Workforce Executions)     │
├─────────────────────────────────────────────────────────┤
│ Layer 3 — Integration (Google Drive / Webhooks / APIs)  │
├─────────────────────────────────────────────────────────┤
│ Layer 4 — Knowledge (Document Repositories / AI Vector) │
└─────────────────────────────────────────────────────────┘
```

---

## 5. Workspace Ownership Model
*   **Status:** `IMPLEMENTED` / `PARTIAL`
*   **Description:** The Workspace is the central business aggregate. It acts as the container for physical assets, listings, transactions, and automated runs.
*   **Code Evidence:** 
    *   [PropertyWorkspace model](../app/Models/PropertyWorkspace.php) (`IMPLEMENTED`).
    *   [PortfolioDriveWorkspace model](../app/Models/PortfolioDriveWorkspace.php) (`LEGACY` / `PARTIAL` — tightly coupled to Google Drive files).
*   **Rule:** Agents never own workspace data. Workspace owns all operational aggregates.

---

## 6. Property Domain
*   **Status:** `IMPLEMENTED`
*   **Description:** The Property aggregate represents the physical asset. It captures physical, geometric, and registry-based truth.
*   **Code Evidence:**
    *   [Property model](../app/Models/Property.php): Extends `BaseModel`, implements value objects.
    *   [Value Objects](../app/Domain/Property/ValueObjects/): [Location.php](../app/Domain/Property/ValueObjects/Location.php), [TapuInfo.php](../app/Domain/Property/ValueObjects/TapuInfo.php), [PhysicalSpecs.php](../app/Domain/Property/ValueObjects/PhysicalSpecs.php).
*   **Rule:** Property attributes (ADA, PARSEL, TKGM ID) are immutable after creation. Pricing and marketing data must never be stored on the Property model.

---

## 7. Property Classification
*   **Status:** `IMPLEMENTED`
*   **Description:** Supports multi-dimensional property classification (e.g., Summer houses, apartments, lands) via detail tables.
*   **Code Evidence:**
    *   [YazlikDetail model](../app/Models/YazlikDetail.php) and corresponding database tables.

---

## 8. Commercial Offering
*   **Status:** `TARGET` / `LEGACY`
*   **Description:** The Commercial Offering represents the commercial intent of a property (Satılık, Kiralık, Sezonluk). It encapsulates prices, commissions, payment terms, and active contracts.
*   **Current State:** Pricing attributes are currently coupled to [Ilan.php](../app/Models/Ilan.php) (`LEGACY`). Decomposing these fields into a dedicated CommercialOffering aggregate is a `TARGET` design for future sprints.

---

## 9. Listing and Publishing
*   **Status:** `IMPLEMENTED`
*   **Description:** Represents channel publication (Airbnb, Sahibinden, Hepsiemlak). A single Property can map to multiple Listings.
*   **Code Evidence:**
    *   [Ilan model](../app/Models/Ilan.php) (extends `BaseModel`, references `property_id`).
    *   [ListingStateMachine.php](../app/Services/Listing/ListingStateMachine.php) and [YalihanLifecycle.php](../app/Services/Listing/YalihanLifecycle.php).
*   **Rule:** Direct state mutation on the model is forbidden; all state changes must pass through the `YalihanLifecycle` transitions.

---

## 10. Reservation Capability
*   **Status:** `PARTIAL` / `LEGACY`
*   **Description:** Tracks guest calendar holds and channel reservation syncs.
*   **Code Evidence:**
    *   [PropertyReservation model](../app/Models/PropertyReservation.php) (`PARTIAL`).
    *   [IlanReservation model](../app/Models/IlanReservation.php) (`LEGACY`).

---

## 11. Finance Capability
*   **Status:** `IMPLEMENTED`
*   **Description:** Double-entry ledger architecture ensuring financial auditing.
*   **Code Evidence:**
    *   [LedgerAccount model](../app/Models/LedgerAccount.php) and [LedgerEntry model](../app/Models/LedgerEntry.php).
    *   Sprint 12E certification artifacts.
    *   *Legacy:* [FinancialTransaction model](../app/Models/FinancialTransaction.php) is `LEGACY`.

---

## 12. Documents and Media
*   **Status:** `PARTIAL`
*   **Description:** Google Drive file synchronizations and title deed OCR uploads.
*   **Code Evidence:**
    *   [Belge model](../app/Models/Belge.php) and subfolders JSON structures in `PortfolioDriveWorkspace`.

---

## 13. AI Capabilities
*   **Status:** `IMPLEMENTED`
*   **Description:** Multi-agent processing, prompt registry, and cost tracking.
*   **Code Evidence:**
    *   [AIOrchestrator.php](../app/Services/AI/AIOrchestrator.php), [YalihanCortex.php](../app/Services/AI/YalihanCortex.php).

---

## 14. Hermes and Agents
*   **Status:** `PARTIAL`
*   **Description:** Hermes operates as the orchestration runtime, routing tasks to specialized agents (e.g., description agent, photo agent).
*   **Code Evidence:**
    *   `app/Services/OpenClaw/` and agent instructions cataloged in `agents/`.

---

## 15. Events and Executions
*   **Status:** `IMPLEMENTED` / `LEGACY`
*   **Description:** Every workspace execution is recorded as an observable, retryable run.
*   **Code Evidence:**
    *   [WorkforceExecution model](../app/Models/WorkforceExecution.php) (`IMPLEMENTED`).
    *   [WorkspaceExecution model](../app/Models/WorkspaceExecution.php) (`LEGACY` / `PARTIAL`).

---

## 16. Tenant Isolation
*   **Status:** `IMPLEMENTED`
*   **Description:** Mandatory tenant scope filtering on all database operations.
*   **Code Evidence:**
    *   [BelongsToTenant trait](../app/Traits/BelongsToTenant.php), global tenant scoping in Eloquent query building.
*   **Rule:** Cross-tenant queries are blocked and result in a security exception.

---

## 17. Replay and Audit Model
*   **Status:** `IMPLEMENTED`
*   **Description:** Retries and replays generate a new execution record with a unique UUID, maintaining the history of the original failure as immutable.
*   **Code Evidence:**
    *   [RecoveryEngineService.php](../app/Services/Execution/RecoveryEngineService.php).

---

## 18. Current Architectural Tensions

1.  **Duplicate Execution Models:** `WorkspaceExecution` (Sprint 4.7) and `WorkforceExecution` (Sprint 13) coexist. While WorkforceExecution is EIOS-canonical, WorkspaceExecution remains in the UI.
2.  **Listing Pricing Coupling:** Pricing parameters continue to live inside the Listings model (`Ilan.php`) instead of a separate `CommercialOffering` aggregate.

---

## 19. Related ADR Candidates

*   **ADR-022:** Workspace is the Business Aggregate
*   **ADR-023:** Property is the Canonical Real-Estate Aggregate
*   **ADR-025:** Commercial Offering is Separate from Property and Listing
*   **ADR-028:** Executions are Auditable and Replay-Safe

---

## 20. Evidence Index

*   [PHASE_2_EVIDENCE_REPORT.md](memory/PHASE_2_EVIDENCE_REPORT.md)
*   [M2-PROPERTY-RUNTIME.md](../.sab/milestones/M2-PROPERTY-RUNTIME.md)
