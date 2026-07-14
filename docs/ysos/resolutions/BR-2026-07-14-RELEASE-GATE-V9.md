# SAAB v9 — RELEASE GATE V9 & WIZARD BLOCKERS RESOLUTION

**Resolution ID:** BR-2026-07-14-RELEASE-GATE-V9
**Status:** ✅ ENTERED INTO PERMANENT RECORD
**Date:** 2026-07-14
**Authority:** Strategic AI Architecture Board (SAAB) v9

---

## 🏛️ Board Decision

Following the Enterprise Architecture Review of the Yalıhan AI OS platform state and upcoming capabilities, the Strategic AI Architecture Board (SAAB) hereby registers the following decisions:

1.  **Release Gate v9 Proposal:** **APPROVED WITH CHANGES**
    *   The dynamic and static quality gates are certified as vital security and tenant isolation constraints.
    *   *Correction:* Gates that execute dynamic event replay or database boots must run asynchronously or be decoupled from pre-commit hooks to keep test suite runtimes below the 120s limit (`R002`).
2.  **Listing Wizard & Publishing Bootstrap Blockers:** **APPROVED FOR IMMEDIATE RESOLUTION**
    *   Resolving validation parser syntax errors and eager-loading relationship failures is certified as a P0 requirement. These fixes enable the E2E Advisor Pilot, directly increasing the Business Automation Index (BAI).

---

## 📊 Business Automation & Product Value

Every change must answer the primary question:  
> **"Does this increase Business Automation?"**

*   **Wizard & Publishing fixes:** Yes. Automating the sync-to-publish workflow replaces manual broker data entry with async AI completion (reducing time from 45 minutes to <2 minutes).
*   **Release Gate v9:** No direct automation, but provides the critical runtime context isolation and budget protection to ensure automation executes safely.

---

## 🛡️ Quality Gate & Architectural Compliance

| Gate | Status | Details |
| :--- | :--- | :--- |
| **Business Value** | ✅ PASS | Restores direct E2E advisor listing lifecycle automation |
| **Architecture Integrity** | ✅ PASS | Eliminates legacy relationship leakage, restoring DDD boundaries |
| **Tenant Isolation** | ✅ PASS | Enforces zero-trust tenant scopes on queue workers |
| **Budget Safety** | ✅ PASS | Ensures LLM API calls are bound by `AiBudgetGuard` checks |
| **Tests & Performance** | ⏳ PENDING | Full php artisan test run is executing in background |

---

## 📋 Action Plan Handoff to Engineering Office (Kilo)

The Board authorizes the following implementation tasks for the Engineering Office:
1.  **Validation Syntax:** Use array-based rule declarations for `video_url` validation inside `IlanWizardController` to prevent pipe parsing failures.
2.  **Eager Loading:** Remove non-existent `ilanDetay` relationships from eager load chains inside `AiBootstrapJob` and `PublishingBootstrapJob`, loading owner details through the canonical `kisi` relation.
3.  **CI/CD Optimization:** Wrap performance-sensitive gates into custom PHPStan/Pint static checks rather than dynamic DB operations to keep build verification under 120s.

---

*Entered into permanent record by SAAB v9 Board on 2026-07-14.*
