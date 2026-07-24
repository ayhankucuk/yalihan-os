# Yalıhan AI OS — Architecture Specification
**Ratified Charter:** SAAB v11.1 (Governance Frozen)  
**Status:** ACTIVE  
**Last Updated:** 2026-07-24  

---

## 1. Purpose and Scope

This document specifies the architecture for **Yalıhan OS** as a **Digital Property Operating System (DPOS)**. It enforces a strict separation between **Current Production Architecture** (active, tested capabilities) and **Target DPOS Architecture** (conceptual roadmap targets).

---

## 2. Source-of-Truth Statement

This specification is subordinate to the **Strategic Architecture & Automation Board (SAAB)** constitution.

*   **Governing Charter:** [SAB.md](SAB.md)
*   **Rules & Constraints:** [.sab/authority.json](../.sab/authority.json)

In the event of conflict between this documentation and the compiled repository code or passing unit tests, the code and tests represent the final truth.

---

## 3. Part I: Current Production Architecture (Active Capabilities)

This section catalogs the active, tested capabilities currently operational in the codebase.

### Core Active Subsystems

#### 1. Property Core System
*   **Status:** `IMPLEMENTED`
*   **Description:** Manages the physical identity and attributes of the real estate assets.
*   **Code Evidence:** 
    *   [Property.php](../app/Models/Property.php) — Physical aggregate root.
    *   [Location.php](../app/Domain/Property/ValueObjects/Location.php), [TapuInfo.php](../app/Domain/Property/ValueObjects/TapuInfo.php), [PhysicalSpecs.php](../app/Domain/Property/ValueObjects/PhysicalSpecs.php) — Immutable value objects.

#### 2. Portfolio Management System (Workspace Boundary)
*   **Status:** `IMPLEMENTED`
*   **Description:** Scopes physical properties and operational runs under Workspace aggregates to prevent tenant data leakage.
*   **Code Evidence:** [PropertyWorkspace.php](../app/Models/PropertyWorkspace.php)

#### 3. Listing & Publishing System (Listing Domain)
*   **Status:** `IMPLEMENTED`
*   **Description:** Marketing representations of properties published to external channels.
*   **Code Evidence:** 
    *   [Ilan.php](../app/Models/Ilan.php) — Listing model referencing `property_id`.
    *   [ListingStateMachine.php](../app/Services/Listing/ListingStateMachine.php), [YalihanLifecycle.php](../app/Services/Listing/YalihanLifecycle.php) — Lifecycle transitions.

#### 4. Finance & Revenue Management (Ledger Engine)
*   **Status:** `IMPLEMENTED`
*   **Description:** Immutable double-entry financial ledger recording matching debit and credit entries.
*   **Code Evidence:** 
    *   [LedgerAccount.php](../app/Models/LedgerAccount.php) & [LedgerEntry.php](../app/Models/LedgerEntry.php) — Ledger tables.
    *   [FinancialLedgerService.php](../app/Services/FinancialLedgerService.php) — Double-entry execution layer.

#### 5. Governance, Audit & Security (Tenant Isolation)
*   **Status:** `IMPLEMENTED`
*   **Description:** Multi-tenant scoping and recovery audit execution logs.
*   **Code Evidence:** 
    *   [BelongsToTenant.php](../app/Traits/BelongsToTenant.php) — Eloquent global tenant filtering.
    *   [WorkforceExecution.php](../app/Models/WorkforceExecution.php) & [RecoveryEngineService.php](../app/Services/Execution/RecoveryEngineService.php) — Replay-safe run audits.

#### 6. Integration Platform (Drive Scaffolding)
*   **Status:** `PARTIAL`
*   **Description:** Integrates Google Drive folder structures when a Drive workspace is bound.
*   **Code Evidence:** [DriveWorkspaceService.php](../app/Services/Drive/DriveWorkspaceService.php)

#### 7. AI Agent Platform (Cortex Pipeline)
*   **Status:** `PARTIAL`
*   **Description:** Multi-agent processing, prompt registry, and cost tracking.
*   **Code Evidence:** `app/Services/AI/YalihanCortex.php` & `app/Services/AI/AIOrchestrator.php`

---

## 4. Part II: Target DPOS Architecture (Roadmap Capabilities)

This section catalogs planned capabilities and conceptual product targets. These are not fully implemented or tested in production code.

### Planned Subsystems

#### 1. Commercial Offering System
*   **Status:** `TARGET` / `LEGACY`
*   **Description:** Decouple commercial intents (Satılık, Kiralık) from Listings. Pricing parameters (`fiyat`, `lansman_fiyati`) currently remain coupled inside `Ilan.php` (`LEGACY`).

#### 2. Reservation & Channel Management
*   **Status:** `PARTIAL` / `LEGACY` (Mock Entegrasyon)
*   **Description:** Channel Manager API adapters. Current codebase relies on stubs returning hardcoded true responses in `CalendarSyncService.php` and `YazlikKiralamaService.php`.

#### 3. Property Operations System
*   **Status:** `PARTIAL` / `LEGACY` (Manual Tasking)
*   **Description:** Automated turnovers, maintenance scheduling, and key delivery. Currently tracked via manually created tasks in `app/Models/Gorev.php`.

#### 4. CRM & Relationship Intelligence
*   **Status:** `PARTIAL` (Legacy Contacts)
*   **Description:** Advanced customer profiles and dynamic lead matching. Relies on legacy `Kisi.php` and `Talep.php` without active matching.

#### 5. Document & Compliance System
*   **Status:** `PARTIAL` (Drive Files Only)
*   **Description:** Title deed OCR, lease contract signatures, and compliance tracking. Currently limited to Drive folders and manual `Belge.php` uploads.

#### 6. Guest & Tenant Experience
*   **Status:** `TARGET`
*   **Description:** Digital check-in, home guide apps, and in-stay service requests.

#### 7. Media & Content Intelligence
*   **Status:** `TARGET`
*   **Description:** AI-based room recognition, photo sorting, and quality scoring.

#### 8. Communication Center
*   **Status:** `TARGET`
*   **Description:** Omnichannel WhatsApp/Airbnb inbox aggregation and AI reply generator.

#### 9. Knowledge & Company Brain
*   **Status:** `TARGET`
*   **Description:** Vectorized workspace context and operational logs.

#### 10. Partner Network System
*   **Status:** `TARGET`
*   **Description:** Shared listings, commission splits, and secure Deal Rooms.

#### 11. Analytics & Decision Intelligence
*   **Status:** `PARTIAL`
*   **Description:** Workspace completion rates and automated Business Automation Index (BAI) logs.

### Target Custom Product Experiences

*   **Property Onboarding Wizard:** Automates the physical registration of new assets.
*   **Property Command Center:** Unified cockpit managing physical, marketing, and financial status.
*   **Advisor Command Center:** Advisor tasks, pipeline, and AI-driven match alerts.
*   **Owner Portal:** Payout ledger visibility and block-date reservations.
*   **Guest App / Digital Guide:** Arrival instructions and local recommendation cards.
*   **Field Operations App:** Mobile cleaning checklists and damage/restock uploaders.
*   **Listing Studio:** Media, descriptions, and channel sync manager.
*   **Deal Room:** Document uploaders and electronic signatures for sales.
*   **Property Intelligence Center:** Dynamic pricing estimates and regional RevPAR graphs.
*   **Finance Control Center:** Split payouts and channel fee reconciliation.
