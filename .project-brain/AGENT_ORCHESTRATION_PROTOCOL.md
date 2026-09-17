# YALIHAN OS — Agent Orchestration Protocol (Level 2 Protocol)

This document defines the multi-agent orchestration architecture, role boundaries, evidence-based conflict resolution, and deployment pipelines for YALIHAN OS.
It complements **YALIHAN OS Agent Constitution v2.1** ([`AGENTS.md`](file:///Users/macbookpro/repos/yalihan-os/AGENTS.md)) and [`TASK_PROTOCOL.md`](file:///Users/macbookpro/repos/yalihan-os/.project-brain/TASK_PROTOCOL.md).

---

## 1. Execution Topology: Single-Agent vs Multi-Agent

Depending on the task setup, agents operate in one of two execution topologies:

### A. Single-Agent Execution (Pair Programming / Interactive Sessions)
In single-agent sessions (e.g., Antigravity IDE, Cline, Codex interactive), a single model instance handles the task through **4 Sequential Phases** rather than separate sub-agents:

```text
Phase 1: Research      (Inspect repository, establish SSOT authority & schema)
       │
       ▼
Phase 2: Implementation (Write code strictly within declared Files Allowed to Modify)
       │
       ▼
Phase 3: Verification   (Run focused unit tests, negative tenant tests, gate scripts)
       │
       ▼
Phase 4: Integration    (Update .project-brain, record evidence, save micro-commit)
```

### B. Multi-Agent Worktree Execution (Parallel Sprints / Epics)
In multi-agent worktree setups (e.g., SAAB parallel research and engineering tasks), responsibilities are split across dedicated agent roles operating in isolated Git Worktrees:

```text
                    LEAD / ORCHESTRATOR
                           │
          ┌────────────────┼────────────────┐
          ↓                ↓                ↓
     RESEARCHER        IMPLEMENTER       VERIFIER
       Alpha              Beta             Gamma
   (Read-Only Repo)   (Dedicated Tree) (Read/Test Tree)
          │                │                │
          └────────────────┼────────────────┘
                           ↓
                      INTEGRATOR
                           │
                           ▼
                 release-candidate/RC2
```

---

## 2. Evidence Hierarchy & Conflict Resolution Rules

### A. Authority Overrides Implementation Preference
When a conflict arises between implementation preferences and canonical architecture:

```text
Convenience
     ↓ (Subordinate to)
Implementation preference
     ↓ (Subordinate to)
Existing documentation
     ↓ (Subordinate to)
Repository evidence & tests
     ↓ (Subordinate to)
Canonical Authority
```

> **RULE:** An lower-level preference (e.g. implementation convenience) CANNOT override a higher-level Canonical Authority.

### B. Evidence Level Priority
When two agents present conflicting findings regarding code behavior:

```text
PRODUCTION_VERIFIED > BROWSER_VERIFIED > TEST_VERIFIED > REPO_VERIFIED > DOCUMENTED > INFERRED
```

### C. Unresolved Ambiguity Protocol
If research and repository evidence remain in contradiction and authority cannot be established:
- NEITHER agent may guess or force an implementation.
- The task status MUST immediately switch to:
  `BLOCKED: ARCHITECTURAL_DECISION_REQUIRED`
- The issue is escalated to the Human Lead Architect (Ayhan) for binding decision.

---

## 3. Merge & Integration Protocol

1. **Separation of Verification & Authoring:** An Implementation Agent (Beta) MUST NOT verify or grade its own work for release.
2. **Verification Gate (Gamma):** Before any feature branch is merged into `release-candidate/RC2`, the Verifier MUST confirm:
   - Code Review PASS
   - Focused Tests PASS
   - Tenant Negative Isolation PASS
   - Data Contract PASS
   - API / UI Flow PASS
3. **Integrator Scope Boundary:** 
   > **Integrator may resolve merge conflicts and synchronize Project Brain metadata, but MUST NOT introduce new business behavior while resolving integration conflicts.**

---

## 4. Production Deployment Pipeline

Local code verification does NOT constitute production readiness. Production deployment follows a strict 8-step pipeline:

```text
CODE (Local Edit)
  │
  ▼
REPO_VERIFIED (Code & Schema Audit PASS)
  │
  ▼
TEST_VERIFIED (PHPUnit & Gate Scripts PASS)
  │
  ▼
INTEGRATED (Merged into release-candidate/RC2)
  │
  ▼
RELEASE CANDIDATE (Pre-flight Gate & Build PASS)
  │
  ▼
PRODUCTION AUTHORIZATION (Explicit Approval by Ayhan)
  │
  ▼
DEPLOY (Docker Container / VPS Image Build)
  │
  ▼
HEALTH CHECK & LOG VERIFICATION
  │
  ▼
PRODUCTION_VERIFIED (End-to-End Business Flow Confirmed)
```

---

## 5. Hermes Event & AI Recommendation Architecture

Hermes is an **Event Broker and Orchestrator**, NOT an AI model. AI systems MUST interface with Hermes strictly via recommendation adapters:

```text
Inbound Event (WhatsApp / Webhook / DB Trigger)
  │
  ▼
Hermes Event Bus (Event ID + Idempotency Key)
  │
  ▼
Routing & Orchestration
  ├── CRM Domain
  ├── Property Matching Engine
  ├── Notification Service
  └── AI Recommendation Adapter
        │
        ▼
  AI Operational Recommendation
        │
        ▼
  Human Approval (Human-in-the-loop Guard)
        │
        ▼
  Application Service → Domain → Repository → DB
```

> **RULE:** AI systems MUST NEVER directly mutate domain database records (e.g., price change, booking cancellation, ledger debit). AI outputs MUST pass through explicit Recommendation + Human Approval before execution.
