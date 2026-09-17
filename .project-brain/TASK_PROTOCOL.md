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
