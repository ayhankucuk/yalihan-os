# Yalıhan OS — Technical Debt Map (Sprint 6.1)

**Role:** Chief Research Office (Antigravity)  
**Classification:** Repository Governance & Debt Prioritization Map  
**Status:** Completed  
**Date:** 2026-07-07  

---

## 🏛️ 1. Executive Summary
This Technical Debt Map consolidates architectural, security, and integration debt across Yalıhan OS. 
We evaluate each item by severity, potential impact, fixing effort, and prioritize them to assist SAAB in roadmap validation for the upcoming Sprint 6.x cycle.

---

## 🗺️ 2. Comprehensive Technical Debt Registry

The grid below maps out the critical debts within the codebase:

| Ref | Category | Description | Severity | Risk | Fix Cost | Priority | Target Sprint |
|-----|----------|-------------|----------|------|----------|----------|---------------|
| **TD-01** | Security | Google Drive Webhook Authentication Bypass (R11) | 🔴 **P0 Blocker** | Webhook verification bypass leading to spoofed events. | Low (1-2h) | High | Sprint 6.1 (Current) |
| **TD-02** | Security | Drive Event log `tenant_id` NULL leak (R12) | 🔴 **P0 Blocker** | Webhook actions written to DB without tenant attribution. | Low (1h) | High | Sprint 6.1 (Current) |
| **TD-03** | Security | Queue Jobs running without Tenant Context (R14) | 🔴 **P0 Blocker** | Cross-tenant data leakage during background processes. | Medium (4-6h) | High | Sprint 6.1 (Current) |
| **TD-04** | Security | Web Panel and CRM Leads lack Tenant Scoping | 🔴 **P0 Blocker** | Tenant Admins can view/edit leads of other tenants. | Medium (4-8h) | High | Sprint 6.1 (Current) |
| **TD-05** | Performance | Webhook Sync lacks Idempotency / Replay Safety | 🟠 **P1 High** | Duplicate webhooks rerun expensive AI agents multiple times. | Medium (3-4h) | Medium | Sprint 6.2 |
| **TD-06** | Integration | TKGM Loopback Deadlock & 404 Route (R13) | 🟠 **P1 High** | Synchronous HTTP geocoding calls trigger local port locks. | Medium (4h) | Medium | Sprint 6.2 |
| **TD-07** | Architecture | Orphaned OutboxService Class (R15) | 🟡 **P2 Medium** | Codebase bloat. Dead service exists but is completely unused. | Low (2h) | Low | Sprint 6.3 |
| **TD-08** | Quality | Linter Naming Violations (59+ instances) | 🟢 **P3 Low** | Database columns and properties use English instead of Turkish. | High (8-12h) | Low | Sprint 6.5 |

---

## 🛡️ 3. Priority Recommendations for Sprint 6.1 & 6.2

* **Sprint 6.1 Focus (Immediate Remediation):**
  We recommend consolidating **TD-01, TD-02, TD-03, and TD-04** into a single "Security Hardening Patch" to be executed by VS Code AI. Resolving these four blockers secures the multi-tenant isolation model, ensuring zero cross-tenant leakages during web dashboard usage, webhook processing, or background queue execution.
  
* **Sprint 6.2 Focus (Stability & Performance):**
  Tackle **TD-05** (Webhook Idempotency) and **TD-06** (TKGM Geocoding) next. By caching processed change IDs and moving TKGM geocoding to an asynchronous event listener, we prevent local deadlock risks and AI token budget waste.
