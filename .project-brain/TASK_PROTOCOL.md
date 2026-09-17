# YALIHAN OS — Task Protocol & Execution Standard (Level 2 Protocol)

This document defines the task classification, execution lifecycle, and escalation protocols for AI agents working on YALIHAN OS.
It complements the immutable **YALIHAN OS Agent Constitution v2.1** ([`AGENTS.md`](file:///Users/macbookpro/repos/yalihan-os/AGENTS.md)).

---

## 1. Task Classification: `QUICK` vs `MATERIAL`

Every incoming user request or task MUST be classified as either `QUICK` or `MATERIAL` before execution:

### A. `MATERIAL` Task Criteria (Mandatory Task Contract)
A task is automatically classified as `MATERIAL` if it involves ANY of the following:
- Schema changes, database migrations, or table structure alterations.
- Domain behavior changes, business logic refactoring, or state machine transitions.
- Authorization, role permissions, or tenant isolation checks (`tenant_id`).
- API contracts, DTOs, request validation rules, or JSON envelope signatures.
- Financial operations, ledgers, commission calculations, or payment integrations.
- Hermes events, queue listeners, async jobs, or webhook handlers.
- AI operational actions, prompt pipelines, or AI recommendation flows.
- Changes impacting more than a single isolated presentation file.

> **RULE:** When in doubt between `QUICK` and `MATERIAL`, the task MUST default to **`MATERIAL`**.

### B. `QUICK` Task Criteria (Exempt from Task Contract)
A task is classified as `QUICK` ONLY if it meets ALL of the following:
- Typo corrections, docblock fixes, or plain text label updates.
- Pure CSS styling tweaks without DOM or state logic changes.
- Read-only research, file viewing, or investigation queries.
- Single-file local tweaks with zero behavioral or cross-layer impact.

---

## 2. Complexity Escalation Rule

If an agent starts execution under a `QUICK` classification and discovers unexpected material impact during investigation (e.g., a simple view fix requires a migration, a tenant scope fix, or a domain authority change):

```text
QUICK
  │ (Discovers unexpected material impact)
  ▼
RECLASSIFY → MATERIAL
  │
  ▼
Output TASK CONTRACT
  │
  ▼
Continue Execution
```

> **RULE:** An agent MUST NOT quietly expand a `QUICK` task to absorb material changes without formal reclassification.

---

## 3. Task Contract Format (`MATERIAL` Tasks Only)

Before modifying code on a `MATERIAL` task, the agent MUST output the following structured `TASK CONTRACT`:

```text
======================================================================
TASK CONTRACT
======================================================================
Task Type:              MATERIAL
Objective:              [Single specific responsibility of the task]
Scope:                  [Boundaries and explicit non-goals]
Canonical Authority:    [Primary canonical Model/Service/Repository being modified]
Files Allowed to Modify:[Max 5-10 explicit file paths]
Read Scope:             [Declared search/inspection scope — read-only repo-wide permitted]
Expected Data Contracts:[Tables, DTOs, interfaces, or API envelopes affected]
Tenant Impact:          [Tenant isolation plan & negative test requirement: YES | NO]
Security Impact:        [Secrets boundary, auth checks, or rate limiting verification]
Production Impact:      [Migration status, deploy order, or human override triggers]
Verification Plan:      [Automated PHPUnit tests, browser E2E flows, or gate scripts]
Stop Conditions:        [Triggers for immediate BLOCKED status]

STATUS: READY | BLOCKED
======================================================================
```

---

## 4. Completion Report Format (All Tasks)

Upon completing execution of any `MATERIAL` task, the agent MUST output a machine-auditable `COMPLETION REPORT`:

```text
======================================================================
COMPLETION REPORT
======================================================================
Code:                   [Files modified and lines changed]
Tests:                  [Passed test suites and assertion count]
Data Contract:          [Verified schema parity and envelope alignment]
Tenant Isolation:       [Passed negative tenant isolation verification]
UI/API Flow:            [Verified HTTP 200 / browser flow rendered cleanly]
Project Brain:          [Updated PROJECT_STATE.md, KNOWN_ISSUES.md, BEKCI_CHANGELOG.md]
Git Commit:             [Commit SHA or Stash reference]
Production:             [PROD_BLOCKED | READY_FOR_DEPLOY]

Evidence Level:         [UNVERIFIED | REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED]

Remaining Risks:        [Any residual risk or pending migration]
Known Issues Logged:    [Issue codes appended to KNOWN_ISSUES.md]
======================================================================
```

## 5. Parent Agent Session Recovery Protocol

Before routing any new `MATERIAL` task to an Implementer or other executor, the Parent Agent (or Router) MUST perform **session recovery reconciliation** to prevent duplicate work and stale context execution.

### A. Session Recovery Checklist

The Parent Agent MUST reconcile the following sources:

1. **Project Brain State:**
   - `.project-brain/PROJECT_STATE.md` (active architectural gates, committed work)
   - `.project-brain/EVIDENCE_INDEX.md` (test-verified or production-verified evidence)
   - `.project-brain/KNOWN_ISSUES.md` (open issues and resolved issues)
   - `.project-brain/DECISION_LOG.md` (accepted ADRs and architectural decisions)

2. **Git Repository State:**
   - `git status --short` (dirty working tree inspection)
   - `git branch --show-current` (current branch verification)
   - `git log -n 8 --oneline` (recent commit history)
   - `git show <commit> --stat` (for specific commits referenced in task context)

3. **Cross-Reference:**
   - Compare task context references (commit SHAs, issue codes, file paths) with actual repository state
   - Classify prior work status: `COMMITTED` | `VERIFIED_NOT_COMMITTED` | `IN_PROGRESS` | `STALE_FINDING` | `BLOCKED`

### B. Reconciliation Decision Tree

**RULE**: Every MATERIAL task MUST perform minimum reconciliation (Section 5.A) before routing. There is NO bypass path.

```text
Minimum reconciliation performed (Section 5.A)?
  │
  └─ YES → Classify task state:
       │
       ├─ Prior work COMMITTED + documented in Project Brain?
       │    └─ YES → Skip duplicate implementation
       │             Report: ALREADY_COMPLETE
       │
       ├─ Prior work COMMITTED but NOT documented in Project Brain?
       │    └─ YES → Route to SYNC task (update Project Brain only)
       │             Do NOT re-implement
       │
       ├─ Prior work IN_PROGRESS (dirty hunks)?
       │    └─ YES → Verify scope alignment
       │             Continue ONLY if bounded scope matches
       │             STOP if scope conflicts
       │
       ├─ Task context references stale commit or non-existent file?
       │    └─ YES → STOP immediately
       │             Report: BLOCKED: STALE_TASK_CONTEXT
       │             Do NOT route to executor
       │
       └─ New/valid task with no conflicts?
            └─ YES → Route to executor normally
```

### C. Mandatory STOP Conditions

The Parent Agent MUST immediately **STOP** and return `BLOCKED: STALE_TASK_CONTEXT` if:

- Task references a commit SHA that does not exist (verify with `git cat-file -e <sha>^{commit}` or `git rev-parse --verify <sha>^{commit}` — absence from `git log` history is NOT sufficient proof of non-existence)
- Task references files that have been moved, renamed, or deleted
- Task assumes architectural state contradicted by current `PROJECT_STATE.md` or `DECISION_LOG.md`
- Task requests duplicate implementation of already-committed work

### D. Project Brain Update Protocol

When routing a **Project Brain synchronization task** (updating documentation to reflect committed work):

1. **Write Scope:** ONLY `.project-brain/` markdown files
2. **Read Scope:** Repository-wide (for verification)
3. **No Code Changes:** Application code, migrations, tests remain untouched
4. **No New Decisions:** `DECISION_LOG.md` is READ-ONLY unless explicitly authorized
5. **Evidence-Based Updates:** All claims must reference commit SHAs, test results, or file line numbers

### E. Session Recovery Task Template

When delegating a Project Brain sync task:

```text
TASK_ID: <ORIGINAL_TASK_ID>_RESUME
INTENT: Resume/recover existing <ORIGINAL_TASK_ID>
DO NOT blindly re-implement.
CURRENT-STATE-FIRST.

Inspect:
  - .project-brain/PROJECT_STATE.md
  - .project-brain/EVIDENCE_INDEX.md
  - .project-brain/KNOWN_ISSUES.md
  - git status --short
  - git log -n 8 --oneline

Classify prior work:
  A) Already fully implemented & committed
  B) Partially implemented (dirty hunks)
  C) Not implemented
  D) Stale/conflicting

If (A): Update Project Brain ONLY. DO NOT re-implement.
If (B): Continue bounded scope ONLY if aligned.
If (C): Proceed with full implementation.
If (D): STOP with BLOCKED: STALE_TASK_CONTEXT
```

