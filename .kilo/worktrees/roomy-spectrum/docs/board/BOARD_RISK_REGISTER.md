# 🏛️ SAAB BOARD RISK REGISTER — YALIHAN AI OS
## Enterprise Risk Assessment

---

**Date:** 2026-06-28
**Assessment Level:** Enterprise
**Review Cycle:** Monthly

---

## Executive Summary

Total Active Risks: **12**
Critical (P0): **3**
High (P1): **5**
Medium (P2): **4**

Overall Risk Score: **6.2/10** (MODERATE)

---

## Risk Matrix

| ID | Risk | Likelihood | Impact | Score | Tier |
|----|------|------------|--------|-------|------|
| R-01 | 89 Failing Tests | HIGH | CRITICAL | 9 | P0 |
| R-02 | Hetzner Deploy Blocker | HIGH | HIGH | 7 | P0 |
| R-03 | JSONB Migration Failure | MEDIUM | CRITICAL | 8 | P0 |
| R-04 | Naming Authority Debt | HIGH | MEDIUM | 6 | P1 |
| R-05 | Tenant Isolation Breach | LOW | CRITICAL | 7 | P1 |
| R-06 | AI Budget Exhaustion | MEDIUM | HIGH | 6 | P1 |
| R-07 | MCP Server Outage | LOW | MEDIUM | 4 | P2 |
| R-08 | Data Projection Drift | MEDIUM | HIGH | 6 | P1 |
| R-09 | N8N Workflow Failure | LOW | MEDIUM | 4 | P2 |
| R-10 | Chief AI Capability Gap | MEDIUM | HIGH | 6 | P1 |
| R-11 | External API Rate Limits | MEDIUM | MEDIUM | 5 | P2 |
| R-12 | SAB Checksum Corruption | VERY LOW | CRITICAL | 5 | P2 |

---

## Detailed Risk Register

### R-01: 89 Failing Tests
**Category:** Quality / Technical Debt
**Likelihood:** HIGH
**Impact:** CRITICAL
**Score:** 9

**Description:**
89 unit/integration tests are failing. This blocks production deployment and indicates underlying quality issues.

**Root Cause:**
- Legacy code compatibility
- Mock/stub mismatches
- Context7 naming changes without test updates

**Mitigation:**
1. Sprint 3 dedicated test remediation sprint
2. Priority-based fix ordering (P0 first)
3. CI gate enforcement (Gold Line)
4. Test coverage requirements for new code

**Owner:** Engineering Office
**Status:** ACTIVE
**Deadline:** Sprint 3 completion

---

### R-02: Hetzner Deploy Blocker
**Category:** Infrastructure / Operations
**Likelihood:** HIGH
**Impact:** HIGH
**Score:** 7

**Description:**
SSH known-hosts issue preventing Hetzner CX33 deployment (157.180.116.63).

**Root Cause:**
- Server host key not registered
- Cloudflare Tunnel configuration pending

**Mitigation:**
1. Manual SSH key registration
2. Cloudflare Tunnel setup for panel subdomain
3. Deploy checklist execution
4. Post-deploy health validation

**Owner:** DevOps
**Status:** BLOCKING
**Dependencies:** R-01

---

### R-03: JSONB Migration Failure
**Category:** Data / Migration
**Likelihood:** MEDIUM
**Impact:** CRITICAL
**Score:** 8

**Description:**
Vertical details tables (turizm, arsa) migration to JSONB column is incomplete. Risk of split-brain data.

**Root Cause:**
- Write path completed
- Read path (ROI Engine, PDF Generator) still reading from old tables

**Mitigation:**
1. Phase 1 complete: Write path to JSONB
2. Phase 2 (pending): Update read path services
3. Validation script before production
4. Rollback procedure documented

**Owner:** Engineering Office
**Status:** ACTIVE
**Task ID:** T-UPS-V2-FULL

---

### R-04: Naming Authority Debt
**Category:** Governance / Compliance
**Likelihood:** HIGH
**Impact:** MEDIUM
**Score:** 6

**Description:**
175 Naming Authority violations remain in codebase. Legacy field names (status, type, active) instead of canonical Turkish names.

**Root Cause:**
- Pre-existing debt from legacy codebase
- Incomplete Context7 adoption

**Mitigation:**
1. Hybrid cleanup plan approved
2. Priority: DB fields first, then code references
3. AST enforcement prevents new violations
4. Gradual reduction target: <50 by Sprint 4

**Owner:** Engineering Office
**Status:** ACTIVE
**Condition:** C4

---

### R-05: Tenant Isolation Breach
**Category:** Security / Compliance
**Likelihood:** LOW
**Impact:** CRITICAL
**Score:** 7

**Description:**
Risk of cross-tenant data access. Tenant isolation is Rule 1 violation.

**Root Cause:**
- Global scopes not consistently applied
- Some repository methods missing tenant filters

**Mitigation:**
1. SabIntegrityScanCommand for detection
2. AST Bekçi v2.1 enforcement
3. TenantContext middleware mandatory
4. Security audit before production

**Owner:** Security Office
**Status:** MONITORED
**Severity:** CRITICAL

---

### R-06: AI Budget Exhaustion
**Category:** Financial / AI Operations
**Likelihood:** MEDIUM
**Impact:** HIGH
**Score:** 6

**Description:**
Uncontrolled AI usage could deplete budget reserves. Phase 12 monetization requires credit controls.

**Root Cause:**
- AI_DRY_RUN currently true (safe mode)
- Production usage without budget guard

**Mitigation:**
1. AiBudgetGuard service in place
2. Credit-based pricing tiers
3. Anomaly detection for velocity spikes
4. Circuit breaker per AI operation

**Owner:** AI Office
**Status:** PREPARED

---

### R-07: MCP Server Outage
**Category:** Infrastructure / Integration
**Likelihood:** LOW
**Impact:** MEDIUM
**Score:** 4

**Description:**
NotebookLM MCP server depends on browser automation. Outage would break AI knowledge access.

**Root Cause:**
- External dependency (NotebookLM, Google)
- Browser session timeout (15 min)

**Mitigation:**
1. Session timeout handling
2. Graceful degradation (local docs fallback)
3. Health check endpoint
4. Alternative: direct Gemini API

**Owner:** Integration Office
**Status:** MONITORED

---

### R-08: Data Projection Drift
**Category:** Data Integrity / CQRS
**Likelihood:** MEDIUM
**Impact:** HIGH
**Score:** 6

**Description:**
Projection tables may drift from core write model over time.

**Root Cause:**
- Event processing failures
- DLQ not monitored
- Projection rebuild not automated

**Mitigation:**
1. DLQ monitoring active
2. projection:rebuild command available
3. projection:health command for status
4. Scheduled rebuild job (weekly)

**Owner:** Operations Office
**Status:** PROTECTED

---

### R-09: N8N Workflow Failure
**Category:** Integration / Automation
**Likelihood:** LOW
**Impact:** MEDIUM
**Score:** 4

**Description:**
N8N automation workflows (listing drafts, contracts, messages) could fail silently.

**Root Cause:**
- Webhook endpoint availability
- N8N service uptime

**Mitigation:**
1. N8N at https://n8n.yalihanemlak.com.tr active
2. Webhook health monitoring
3. DLQ for async operations
4. Fallback: direct API calls

**Owner:** Integration Office
**Status:** OPERATIONAL

---

### R-10: Chief AI Capability Gap
**Category:** Management / AI
**Likelihood:** MEDIUM
**Impact:** HIGH
**Score:** 6

**Description:**
Chief AI management layer is in concept stage. Full orchestration capabilities not yet implemented.

**Root Cause:**
- New capability, early development
- Multiple competencies to implement

**Mitigation:**
1. Phased implementation (Sprint 3-6)
2. Core competencies first (Planning, Audit)
3. Integration with existing tools
4. Iterative improvement

**Owner:** Chief AI Officer
**Status:** PLANNED
**Timeline:** Sprint 3-6

---

### R-11: External API Rate Limits
**Category:** Integration / AI Providers
**Likelihood:** MEDIUM
**Impact:** MEDIUM
**Score:** 5

**Description:**
AI providers (DeepSeek, OpenAI) have rate limits. Exceeding causes service degradation.

**Root Cause:**
- High-volume AI operations
- Provider quota limits

**Mitigation:**
1. RoutedCortexExecutor with fallback chain
2. Ollama as local fallback
3. Rate limit monitoring
4. Exponential backoff retry

**Owner:** AI Office
**Status:** PROTECTED

---

### R-12: SAB Checksum Corruption
**Category:** Governance / Integrity
**Likelihood:** VERY LOW
**Impact:** CRITICAL
**Score:** 5

**Description:**
SAB.md checksum mismatch could block all changes if corrupted.

**Root Cause:**
- Manual checksum file editing
- CI/CD failure

**Mitigation:**
1. Checksum regeneration via sab-propose.sh
2. CI gate on checksum match
3. Versioned backups
4. Manual override requires Board approval

**Owner:** Governance Office
**Status:** PROTECTED

---

## Risk Response Strategies

| Strategy | Risks | Count |
|----------|-------|-------|
| MITIGATE | R-01, R-02, R-03, R-04, R-08, R-10 | 6 |
| MONITOR | R-05, R-07, R-09, R-11, R-12 | 5 |
| ACCEPT | — | 0 |
| TRANSFER | — | 0 |
| AVOID | — | 0 |

---

## Risk Trend Analysis

| Month | Risk Score | Trend |
|-------|------------|-------|
| 2026-05 | 7.8 | Baseline |
| 2026-06 | 6.5 | ↓ Improved |
| 2026-06-28 | 6.2 | ↓ Trending down |

---

## Board Risk Appetite

| Category | Appetite | Current Status |
|----------|----------|----------------|
| Technical Debt | LOW | ⚠️ 89 tests |
| Security | ZERO | ✅ Protected |
| Governance | ZERO | ✅ SAB Active |
| AI Operations | MEDIUM | ✅ Budget Guards |
| Infrastructure | LOW | ⚠️ Deploy pending |

---

*Risk register reviewed and approved by Board of Directors.*
*Next review: 2026-07-28*
