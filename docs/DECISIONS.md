# Yalıhan AI OS — Architecture Decisions Log
**Ratified Charter:** SAAB v11.1 (Governance Frozen)  
**Status:** ACTIVE  
**Last Updated:** 2026-07-24  

This index catalogs the recovered architectural decisions for Yalıhan AI OS. It tracks their readiness for conversion into formal ADRs.

---

## ADR Candidate Catalog

### ADR-022: Workspace Is the Business Aggregate
*   **Domain:** PropertyWorkspace
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [PropertyWorkspace model](../app/Models/PropertyWorkspace.php)
    *   [TenantIsolationSafetyTest](../tests/Feature/Security/TenantIsolationSafetyTest.php)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-023: Property Is the Canonical Real-Estate Aggregate
*   **Domain:** Property
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [Property model](../app/Models/Property.php)
    *   [M2 Milestone report](../.sab/milestones/M2-PROPERTY-RUNTIME.md)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-024: Property Classification Is Multi-Dimensional
*   **Domain:** Property
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [YazlikDetail model](../app/Models/YazlikDetail.php)
    *   Database schema migrations for classification details
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-025: Commercial Offering Is Separate from Property and Listing
*   **Domain:** Finance / Marketing
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** Medium (50%)
*   **Primary Evidence Paths:**
    *   Documented as architectural intent in [SAB.md](SAB.md#L141-L151)
*   **Unresolved Conflict:** Pricing and commercial attributes remain coupled to the Listings model ([Ilan.php](../app/Models/Ilan.php)).
*   **Readiness:** REQUIRES SAAB DECISION (Implementation pending)

### ADR-026: Listing Is a Publication Representation
*   **Domain:** Listing
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [Ilan model](../app/Models/Ilan.php)
    *   [ListingLifecycleFinalSealTest](../tests/Feature/ListingLifecycle/ListingLifecycleFinalSealTest.php)
*   **Unresolved Conflict:** Couples transactional pricing data (`fiyat`, `lansman_fiyati`) due to the absence of the Commercial Offering aggregate.
*   **Readiness:** READY FOR DRAFT

### ADR-027: Important State Changes Use Immutable Events
*   **Domain:** Hermes / Core
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [ListingStateTransition model](../app/Models/ListingStateTransition.php)
    *   [ListingStateMachine](../app/Services/Listing/ListingStateMachine.php)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-028: Executions Are Auditable and Replay-Safe
*   **Domain:** Hermes / Execution
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [WorkforceExecution model](../app/Models/WorkforceExecution.php)
    *   [RecoveryEngineService](../app/Services/Execution/RecoveryEngineService.php)
*   **Unresolved Conflict:** Coexistence of `WorkspaceExecution` (legacy, auto-increment based) and `WorkforceExecution` (EIOS-canonical, UUID-based).
*   **Readiness:** REQUIRES SAAB DECISION (For model integration plan)

### ADR-029: Finance Uses Append-Only Financial Events
*   **Domain:** Finance
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   Financial event log streams documented under Sprint 12E
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-030: Monetary Values Avoid Floating-Point Arithmetic
*   **Domain:** Finance
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   Money, BasisPoints, and Currency VOs implemented in Sprint 12E
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-031: Tenant Isolation Is Mandatory
*   **Domain:** Core / Security
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [BelongsToTenant trait](../app/Traits/BelongsToTenant.php)
    *   [KomisyonControllerTenantIsolationTest](../tests/Feature/Admin/KomisyonControllerTenantIsolationTest.php)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-032: Hermes Orchestrates and Agents Execute
*   **Domain:** AI / Hermes
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [SAB.md Section 7](SAB.md#L174-L188)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-033: Repository Is the Highest Operational Source of Truth
*   **Domain:** Governance
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [SAB.md Section 2](SAB.md#L78-L105)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-034: EIOS Defines Standard Execution Semantics
*   **Domain:** EIOS
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [EIOS-BOOTSTRAP.md](../.agents/EIOS-BOOTSTRAP.md)
    *   [EIOS-RUNTIME.md](../.agents/EIOS-RUNTIME.md)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-035: Business Automation Index Measures Automation Progress
*   **Domain:** Execution
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [ExecutionMetricsService](../app/Services/Execution/ExecutionMetricsService.php)
    *   Sprint 14 metric reports
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-036: SAAB Governs Strategic Architecture
*   **Domain:** Governance
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [SAB.md](SAB.md)
    *   [.sab/authority.json](../.sab/authority.json)
*   **Unresolved Conflict:** None
*   **Readiness:** READY FOR DRAFT

### ADR-037: Repository Atlas Guides Workspace Rationalization
*   **Domain:** Repository / Governance
*   **Proposed Status:** Proposed
*   **Evidence Confidence:** High (100%)
*   **Primary Evidence Paths:**
    *   [Repository Atlas](REPOSITORY_ATLAS.md)
*   **Unresolved Conflict:** Contains archive-candidate folders and obsolete scripts that need review before removal.
*   **Readiness:** READY FOR DRAFT
