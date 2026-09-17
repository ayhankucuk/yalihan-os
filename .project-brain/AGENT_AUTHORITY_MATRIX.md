# YALIHAN OS — Agent Authority Matrix (Level 2 Protocol)

This document defines the strict operational boundaries, permission matrix, and authorization constraints for AI agents and human roles working on YALIHAN OS.
It complements **YALIHAN OS Agent Constitution v2.1** ([`AGENTS.md`](file:///Users/macbookpro/repos/yalihan-os/AGENTS.md)) and [`AGENT_ORCHESTRATION_PROTOCOL.md`](file:///Users/macbookpro/repos/yalihan-os/.project-brain/AGENT_ORCHESTRATION_PROTOCOL.md).

---

## 1. Operational Permission Matrix

| Operation / Action | Research (Alpha) | Implementer (Beta) | Verifier (Gamma) | Integrator | Human Authority (Ayhan) |
|:-------------------|:----------------:|:------------------:|:----------------:|:----------:|:-----------------------:|
| **Repository Read & Search** | ✅ Allowed | ✅ Allowed | ✅ Allowed | ✅ Allowed | ✅ Full Authority |
| **Code Modification** | ❌ Forbidden | ✅ Dedicated Tree | ❌ Forbidden | ⚠️ Conflict-Fix Only | ✅ Full Authority |
| **Run Automated Tests** | ✅ Allowed | ✅ Allowed | ✅ Allowed | ✅ Allowed | ✅ Full Authority |
| **Architecture / SSOT Changes** | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | 🔑 **Explicit Approval** |
| **Create Migrations** | ❌ Forbidden | ⚠️ Controlled | ❌ Forbidden | ❌ Forbidden | 🔑 **Explicit Approval** |
| **Apply Production Migrations** | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | ⚠️ Controlled | 🔑 **Explicit Approval** |
| **Production Deploy** | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | ⚠️ Controlled | 🔑 **Explicit Approval** |
| **Destructive DB Operations** | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | 🔑 **Explicit Approval** |
| **Direct DB Price / Financial Mutation** | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | ❌ Forbidden | 🔑 **Explicit Approval** |
| **Certification / Sign-Off** | ❌ Forbidden | ❌ Forbidden | ✅ Verification | ❌ Forbidden | 🔑 **Final Sign-Off** |

---

## 2. Guard Rules & Scope Enforcement

1. **Code Modification vs Architectural Authority:**
   > `"Ability to write code DOES NOT EQUAL authority to decide architecture."`
   If an Implementer agent encounters an ambiguous architecture or split-brain model, it MUST NOT pick a favorite or create a parallel model. It MUST halt and request human architectural decision (`BLOCKED: ARCHITECTURAL_DECISION_REQUIRED`).

2. **Integrator Conflict Resolution Boundary:**
   > `Integrator may resolve merge conflicts and synchronize Project Brain metadata, but MUST NOT introduce new business behavior while resolving integration conflicts.`

3. **Human Approval Thresholds:**
   The following actions CANNOT be performed by any AI agent without explicit real-time human approval:
   - `DROP`, `TRUNCATE`, or broad `DELETE` on production database tables.
   - Modifying property prices (`fiyat`), reservation statuses, or ledger balances.
   - Deleting core CRM entities, user accounts, or active property listings.
   - Applying database schema migrations to production environments.
