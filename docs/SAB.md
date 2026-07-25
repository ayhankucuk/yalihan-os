---
id: sab-v11
schema_version: 1.0
version: "11.1"
status: canonical
owner: saab
domain: governance
created_at: 2026-07-15
reviewed_at: 2026-07-15
review_due: 2027-07-15
supersedes: []
superseded_by: []
evidence: {}
tags:
  - governance
  - saab
  - architecture
---

# SAAB v11.1 — Strategic Architecture & Automation Board

**Status:** ACTIVE
**Board Resolution:** BR-20260715-SAABv11
**Amendment:** BR-20260715-SAABv11.1 — Dual Board Charter
**Ratified:** 2026-07-15T22:55:19+03:00
**Applies to:** Entire Repository
**Target Sprints:** Sprint 10+

---

## Preamble

YALIHAN OS is an **AI-First Digital Property Intelligence Platform** built on EIOS.

Primary objective:

> **Increase Business Automation Index (BAI) through safe, observable and evidence-driven automation.**

Every implementation must reduce manual work.

---

## SAAB v11 Stable Governance

**SAAB v11 is a stable governance document.**

This means:

| Policy | Rule |
|--------|------|
| New sections | **Forbidden** — SAAB does not grow per sprint |
| Scope creep | **Forbidden** — New ideas go to ADR first |
| Bug fixes | **Allowed** — Corrections and clarifications only |
| Board proposals | **Allowed** — For genuinely new governance rules |

**The SAAB Charter is the last section added to SAAB until v12 or higher.**

New ideas follow this path:
```
Idea
  ↓
ADR (PROPOSED)
  ↓
ADR (ACTIVE) — if widely applicable governance rule
  ↓
SAAB v12 or higher
```

---

## Document Lifecycle Policy

Every document in the governance system has a defined lifecycle:

| Document | Lifecycle | Meaning |
|----------|-----------|---------|
| **SAAB** | Stable | Frozen governance charter. Changes require Board Resolution. |
| **ADR** | Experimental | Proposed, debated, ratified. Can become Stable or be REMOVED. |
| **Blueprint** | Living | Updated per sprint with implementation details. |
| **Registry** | Generated | Produced by tooling (not written manually). Auto-validated. |
| **Evidence** | Immutable | Once recorded, never changed. Append-only for corrections. |

**This separation prevents governance bloat.** SAAB grows only by formal Board Resolution. ADRs handle experimentation. Evidence proves without revision.

---

## 1. Mission

The SAAB governs architecture decisions, implementation discipline, and business automation for YALIHAN OS.

Every layer must answer: *What manual real estate work disappears after this change?*

If none, do not expand scope.

---

## 2. Governance Hierarchy

Every decision follows this order:

```
Board Resolution
        ↓
Enterprise Blueprint
        ↓
ADR
        ↓
Registry
        ↓
Implementation
        ↓
Evidence
        ↓
Certification
```

Nothing bypasses this hierarchy.

**SAAB Rule 1:** Lower layers may never redefine upper layers.
**SAAB Rule 2:** Discovery documents never implement code.
**SAAB Rule 3:** Implementation sprints never redefine architecture.
**SAAB Rule 4:** Architecture changes require Board approval.

---

## 3. Architecture Layers

```
Layer 1 — Governance
Layer 2 — Blueprint
Layer 3 — Enterprise Memory
Layer 4 — Runtime
Layer 5 — Business Automation
```

Each layer has specific responsibilities and may not bypass its boundaries.

---

## 4. Canonical Business Model

**Workspace** is the business aggregate. Workspace is always the source of truth.

Workspace owns:

- Properties
- Listings
- CRM
- Documents
- Media
- Timeline
- AI Analysis
- Executions

---

## 5. Canonical Property Model

**Property** represents the physical asset. Property contains only physical truth.

Property owns:
- TKGM Identity
- Geometry
- Physical Facts
- Immutable references

Property never owns: Price, Publication, Marketing, CRM, Reservations — those belong elsewhere.

---

## 6. Listing Model

**Listing** represents publication. Property → 1:N Listings.

```
Property
      │
      ├── Listing (YALIHAN)
      ├── Listing (Airbnb)
      ├── Listing (Sahibinden)
      └── Listing (...)
```

Listing owns:
- price
- title
- description
- publication status
- selected media

---

## 7. Hermes Runtime

**Hermes is NOT an agent.** Hermes is the Runtime Operating System.

Responsibilities:
- Read registries
- Choose capabilities
- Orchestrate agents
- Manage executions
- Write audit trail
- Publish timeline

**Hermes owns no business data.**

---

## 8. AI Workforce

| Role | Responsibility |
|------|----------------|
| Agents | Execute |
| Capabilities | Describe |
| Offices | Organize |
| Registries | Discover |
| Hermes | Orchestrate |
| Workspace | Owns data |

---

## 9. Registry First

Before changing architecture: **Registry must know it.**

Required registries:
- Models
- Controllers
- Routes
- Capabilities
- Agents
- Offices
- Dashboards

**Implementation without registry update is forbidden.**

---

## 9.1 Registry Lifecycle

Registry is a governance object, not just a JSON file. Every registry entry follows this lifecycle:

```
DISCOVERED
    ↓
CLASSIFIED
    ↓
VALIDATED
    ↓
FROZEN
    ↓
CERTIFIED
```

| State | Meaning |
|-------|---------|
| DISCOVERED | Found during code scan, not yet reviewed |
| CLASSIFIED | Assigned to a domain, capability, or layer |
| VALIDATED | Verified to exist and match its classification |
| FROZEN | Locked for the sprint — no changes without Board approval |
| CERTIFIED | Verified correct in production, permanently recorded |

Registry entries never skip states. Retroactive state changes require Board approval.

---

## 10. ADR Lifecycle

Allowed lifecycle states:

```
PROPOSED
    ↓
REVISED
    ↓
RATIFIED
    ↓
IMPLEMENTED
    ↓
CERTIFIED
    ↓
ACTIVE
    ↓
DEPRECATED
    ↓
REMOVED
```

**No ADR becomes ACTIVE before implementation certification.**

---

## 11. Discovery Before Transformation

Before changing code:

1. Discover
2. Classify
3. Validate
4. Decide
5. Implement

**Never reverse the order.**

---

## 12. Model Classification

Every component must be classified. States:

| State | Meaning |
|-------|---------|
| CANONICAL | Approved, production-ready |
| TRANSITION | Migration in progress |
| LEGACY | Old pattern, planned migration |
| DEPRECATED | Will be removed |
| REMOVED | Deleted |

**SAAB Rule 5:** Never remove UNKNOWN code. Never remove TRANSITION code without migration evidence.

---

## 13. Evidence First

Every implementation produces evidence. Evidence is immutable.

Evidence includes:
- Tests
- Registry
- Certification
- Changelog
- Metrics

---

## 14. Enterprise Memory

| Type | Purpose |
|------|---------|
| Repository | Institutional memory |
| Registry | Machine memory |
| Documentation | Explains |
| Code | Executes |
| Evidence | Proves |

---

## 15. Runtime Principles

Every execution must have:
- Owner
- Timestamps
- Logs
- Metrics
- Replay support
- Retries
- Audit trail

---

## 15.1 Runtime Evolution Rule

Hermes Runtime evolves independently. Business stability is paramount.

**Business domains never depend on Hermes implementation details.**

| May change freely | Must remain stable |
|-------------------|--------------------|
| Hermes internals | Business aggregates |
| Agent implementations | Property truth model |
| Capability definitions | Listing contracts |
| Registry structure | Workspace boundaries |
| Orchestration logic | Tenant isolation |

When Hermes changes, business code must not need to change. If it does, the architecture has leaked.

---

## 16. Quality Gates

Every sprint must pass:

| Gate | Description |
|------|-------------|
| Tests | Unit & Integration |
| Registry Validation | All registries updated |
| Freeze Check | No breaking changes |
| Replay Safety | DLQ replay verified |
| Tenant Isolation | Cross-tenant zero |
| Security | Auth & permissions |
| Documentation | Up to date |
| Certification | SAAB certified |

---

## 16.1 Business Value Gate

Every implementation must satisfy at least one:

| Value Type | Description |
|------------|-------------|
| increases BAI | Directly advances Business Automation Index |
| reduces manual work | Removes or automates human tasks |
| reduces execution time | Measurable latency or throughput improvement |
| reduces operational risk | Removes a known failure mode |
| improves observability | Adds metrics, traces, or audit capability |

Technical excellence without business value is not sufficient. A sprint that passes all technical gates but satisfies none of the above has not delivered.

---

## 17. Milestones

| Milestone | Name | Outcome |
|-----------|------|---------|
| M1 | ERA IV Foundation | Core infrastructure operational |
| M2 | Property Runtime | Physical asset truth fully captured |
| M3 | Enterprise Knowledge | All institutional knowledge indexed |
| M4 | Autonomous Runtime | Self-healing, self-optimizing operations |
| M5 | Autonomous Enterprise | Full Business Automation Index achieved |

Each milestone has defined exit criteria. Milestones are never declared complete without evidence.

---

## 18. EIOS Relationship

```
EIOS
      ↓
Platform Engine
      ↓
YALIHAN OS (Reference Application)
```

- YALIHAN validates EIOS
- EIOS must remain reusable beyond YALIHAN

**This is a platform play.** YALIHAN is the first reference implementation. EIOS must be designed to serve any vertical — real estate, healthcare, logistics — without coupling to YALIHAN-specific logic.

---

## 19. Final Rule

Before every implementation ask:

1. Which Blueprint chapter?
2. Which ADR?
3. Which Capability?
4. Which Office?
5. Which Registry changes?
6. Which BAI metric improves?
7. What evidence will prove success?

**If these cannot be answered, implementation does not begin.**

---

## 20. Definition of Done

A task is **Done** when:
- [ ] Blueprint chapter identified
- [ ] ADR exists or created
- [ ] Capability specified
- [ ] Office designated
- [ ] Registry updated (and lifecycle state recorded)
- [ ] Business Value Gate satisfied
- [ ] Evidence defined
- [ ] Tests pass
- [ ] Registry validated
- [ ] SAAB certified

---

## 22. Dual Board Charter

Two boards govern YALIHAN OS. Each has a distinct mandate. Neither can override the other without Board Resolution.

### 22.1 SAAB Decision Board (Blue)

**Mandate:** Architecture and Governance Authority

The Decision Board owns:
- ADR approvals
- Capability sign-offs
- Sprint authorizations
- Architecture change decisions
- Blueprint chapter assignments

The Decision Board asks:
- *Does this ADR fit the mission?*
- *Does this capability serve BAI?*
- *Is this sprint authorized?*
- *Which registry changes?*
- *What evidence proves success?*

### 22.2 SAAB Red Team (Red)

**Mandate:** Complexity and Waste Reduction Authority

The Red Team owns:
- Complexity reduction
- Capability audits
- Agent, dashboard, route, and abstraction audits
- Waste identification

The Red Team asks:
- *Can we simplify this?*
- *Do we really need this capability?*
- *Is this agent, route, dashboard, or abstraction necessary?*
- *What happens if we remove it?*
- *What manual minutes does this add vs. save?*

### 22.3 Operating Protocol

| Rule | Description |
|------|-------------|
| Both boards are equal | Neither board overrides the other without Board Resolution |
| Red Team challenges everything | No implementation reaches Decision Board without Red Team review |
| Decision Board authorizes | Red Team identifies problems; Decision Board decides resolution |
| Complexity budget | Every new capability must justify its weight. Red Team tracks cumulative weight. |
| Auto-deprecation | Red Team may flag capabilities, agents, routes, or abstractions for deprecation review |

### 22.4 Red Team Audit Triggers

The Red Team reviews on:

| Trigger | Frequency |
|---------|-----------|
| Pre-Sprint | Mandatory before sprint planning |
| Pre-Release | Mandatory before release |
| BAI plateau | When BAI stops improving for 2+ sprints |
| Registry bloat | When registry growth exceeds 20% per sprint |
| Capability creep | When capability count exceeds roadmap without justification |

### 22.5 Red Team Report Format

Every Red Team review produces a structured report. The report is evidence, not opinion.

| Field | Required | Description |
|-------|----------|-------------|
| **Complexity Cost** | Yes | Estimated increase in system complexity (Low / Medium / High / Critical) |
| **BAI Impact** | Yes | Positive / Negative / Neutral / Unknown |
| **Alternative** | Yes | Simpler solution, or confirmation no alternative exists |
| **Recommendation** | Yes | APPROVE / REVISE / REJECT |

**REJECT requires an Alternative.** Red Team never rejects without offering a simpler path.
**REVISEd items return to Decision Board with alternatives.**
**APPROVEd items proceed with Red Team noted in the record.**

**Business Value Question (mandatory):**

> *If this change were not made, which real business operation would the user be unable to perform?*

If the answer is unclear or the operation is non-critical, the change is likely not a priority. This question converts Red Team from an objection board into a business prioritization filter.

### 22.6 Dual Board Decision Flow

```
Red Team Review (structured report)
      ↓
[Flagged for Reduction] → Decision Board approves removal
      ↓
[Approved for Development] → Decision Board authorizes sprint
      ↓
Implementation
      ↓
Evidence
      ↓
BAI Measurement
      ↓
Post-Implementation Review ← ← ← ← ←
      ↓                            │
Certification                      │
      ↓                            │
[BAI < Expected] → Red Team flags │
  new ADR                          │
  ↺                                │
[BAI ≥ Expected] → Documented      │
```

---

## 23. Sprint Scorecard

Every sprint produces a scorecard. The scorecard is evidence. It links Board decisions to measurable outcomes.

| Metric | Target | Actual | Decision |
|--------|--------|--------|----------|
| BAI Gain | +10% | ? | Review / Accept |
| Manual Minutes Saved | Per capability | ? | Accept / Reject |
| Registry Growth | ≤+5 entries | ? | Accept / Red Team Review |
| Runtime Reliability | ≥99% | ? | Pass / Fail |
| Test Coverage | ≥90% | ? | Pass / Fail |
| Post-Implementation BAI | ≥Expected | ? | Accept / New ADR |

**Decision rules:**
- **Accept:** Actual ≥ Target
- **Review:** Actual within 10% of Target
- **Red Team Review:** Registry Growth > +5 without justification
- **New ADR:** BAI < Expected after Post-Implementation Review

The scorecard is appended to the Sprint Evidence record. It is never used to punish. It is used to calibrate the next Board decision.

---

## 24. Governance Freeze

SAAB v11.1 is frozen until v12 or higher.

The following structure is complete and stable:

- ✅ Governance hierarchy
- ✅ ADR Lifecycle
- ✅ Registry First
- ✅ Evidence First
- ✅ Certification
- ✅ BAI-first governance
- ✅ Dual Board (Decision + Red Team)
- ✅ Post-Implementation Review
- ✅ Sprint Scorecard
- ✅ Business Value Gate

**From Sprint 12 onwards:** Focus shifts from governance to Business Capability production.

**New success criterion:**

> *At the end of this sprint, which real estate operation does YALIHAN complete without human intervention or with measurably less intervention?*

If the sprint cannot answer this question, the sprint did not deliver business value regardless of technical output.

---

## 21. Legacy Rules (Preserved from v24.2.0)

The following rules from previous SAB versions remain active:

### 21.1 Core Database Rules
1. **Core (Ledger / CRM Write DB) is IMMUTABLE**
2. **Core write is forbidden** — Mutation only through Service layer
3. **Observer bypass is forbidden**
4. **Raw DB write is forbidden** (migration excluded)
5. **Projection tables are Read Model only**
6. **Integration layer is Advisory Only**
7. **Core analytics/joins are forbidden**

### 21.2 Error Handling
- **Silent catch is forbidden** (Fail-Fast mandatory, AST Bekçi v2.1)
- **DLQ is mandatory** and replay must be verified
- **Event processing must be idempotent**

### 21.3 Context7 Naming Authority
- **Context7 ihlal toleransı = 0**
- Forbidden field aliases remain in effect:
  - `type` → `tip`
  - `description` → `aciklama`
  - `category` → `kategori`
  - `status` → `yayin_durumu`
  - `active` / `is_active` → `aktiflik_durumu`
  - `order` / `sort_order` → `display_order`
  - `featured` → `one_cikan`
  - `featured_image` → `kapak_resmi`

### 21.4 Financial Rules (Phase 12)
- **Multi-Tenant Financial Scoping:** `tenant_id` mandatory in all financial queries
- **AI Circuit Breaker:** Every AI operation subject to `AiBudgetGuard`
- **Financial Integrity:** Balance mutations only via `recordDoubleEntry`

### 21.5 Context Isolation (ADR-041)
- Office P0 context budget: 0–80K normal | 80–120K warning | 120–150K freeze | >150K new session
- Conversation history is disposable — corporate memory is approved artifacts only

---

## SAAB v11.1 Motto

> **"Architecture is decided by evidence, implemented with discipline, and certified through measurable business value."**

---

## Appendix: Version History

| Version | Date | Change |
|---------|------|--------|
| v24.2.0 | 2026-06-25 | Phase 12-15: Financial seal, Context isolation |
| v11 | 2026-07-15 | Strategic Architecture & Automation Board — BAI, EIOS, Registry Lifecycle (9.1), Business Value Gate (16.1), Runtime Evolution (15.1), Stable Governance, Document Lifecycle Policy, Milestones M1-M5 (output-oriented), Sprint 10 Success Criteria |
| v11.1 | 2026-07-15 | Dual Board Charter — Decision Board + Red Team, Section 22; Red Team Report Format with Business Value Question (22.5); Decision Flow + Post-Implementation Review (22.6); Sprint Scorecard (Section 23); Governance Freeze with Sprint 12 Business Capability mandate (Section 24) |

---

## Appendix: Scorecard

| Dimension | Assessment |
|-----------|------------|
| Architectural consistency | Strong |
| Governance discipline | Strong |
| Implementability | High — Sprint 10+ ready |
| Business alignment | High — BAI-first mission |
| Stable Governance | Enforced — no per-sprint SAAB growth |
| Document Lifecycle | Defined — SAAB Stable, ADR Experimental, Blueprint Living, Registry Generated, Evidence Immutable |

---

## Appendix: Sprint 10 Success Criteria

Sprint 10 is the first implementation sprint under SAAB v11. Success is measured by working, provable capabilities — not document count.

| # | Criterion | Evidence |
|---|-----------|----------|
| 1 | Working `Property` aggregate | CRUD operations pass, tests green |
| 2 | Working state machine | State transitions validated |
| 3 | Registry updated | DISCOVERED → CLASSIFIED entries exist |
| 4 | Replay-safe tests | DLQ replay verified |
| 5 | BAI impact visible | First BAI metric improvement recorded |

**Success means:** Working code + measurable business value + evidence trail. Nothing else.

---

*This charter protects the technical integrity and commercial future of Yalıhan AI OS.*
