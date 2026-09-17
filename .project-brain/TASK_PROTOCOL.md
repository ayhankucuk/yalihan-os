# YALIHAN OS — Task Protocol & Execution Standard

This document defines the operational execution standard for all AI agents working on YALIHAN OS.
It complements the immutable **YALIHAN OS Agent Constitution v2.1** ([`AGENTS.md`](file:///Users/macbookpro/repos/yalihan-os/AGENTS.md)).

---

## 1. Task Contract Standard (Before Starting Any Task)

Every agent MUST output a structured `TASK CONTRACT` block before modifying code or performing material architectural work:

```text
======================================================================
TASK CONTRACT
======================================================================
Objective:              [Single specific responsibility of the task]
Scope:                  [Boundaries and explicit non-goals]
Canonical Authority:    [Primary canonical Model/Service/Repository being modified]
Files Allowed to Modify:[Max 5-10 explicit file paths]
Read Scope:             [Declared search/inspection scope — read-only repo-wide permitted]
Expected Data Contracts:[Tables, DTOs, interfaces, or API envelopes affected]
Tenant Impact:          [Tenant isolation verification plan & negative test requirement]
Security Impact:        [Secrets boundary, auth checks, or rate limiting verification]
Production Impact:      [Migration status, deploy order, or human override triggers]
Verification Plan:      [Automated PHPUnit tests, browser E2E flows, or gate scripts]
Stop Conditions:        [Triggers for immediate BLOCKED status]

STATUS: READY | BLOCKED
======================================================================
```

---

## 2. Completion Report Standard (Upon Task Completion)

Upon completing execution, the agent MUST output a machine-auditable `COMPLETION REPORT` block:

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

---

## 3. Multi-Agent Orchestration Protocol (Inter-Agent Governance)

When multiple agents run concurrently across Git Worktrees, the following role hierarchy and conflict resolution rules govern operations:

### A. Role Division & Capabilities

| Agent Role | Primary Duty | Write Authority | Merge Authority | Allowed Tools |
|------------|--------------|-----------------|-----------------|---------------|
| **Research Office (Alpha)** | Architecture audit, debt discovery, SAAB reviews | Read-Only (Docs/Brain only) | NONE | `grep`, `find`, `view_file`, `bekci:health` |
| **Engineering Office (Beta)** | Code implementation, feature development | Dedicated Worktree | Local Branch | Code editing, unit testing, migrations |
| **Verification Office (Gamma)** | E2E certification, preflight, gate checks | Quality Gates & Logs | RC2 Release Gate | `antigravity-full-gate.sh`, browser E2E |

### B. Inter-Agent Conflict Resolution Rules

1. **Evidence Hierarchy Overrules Inference:**
   When two agents contradict regarding code behavior, the higher evidence level prevails:
   `PRODUCTION_VERIFIED > BROWSER_VERIFIED > TEST_VERIFIED > REPO_VERIFIED > DOCUMENTED > INFERRED`

2. **Authority Lock & Priority:**
   - If Agent Alpha locks a module in `.project-brain/PROJECT_STATE.md`, Agent Beta MUST NOT modify that module in the main worktree.
   - If two agents attempt to modify the same canonical entity simultaneously, the second agent MUST STOP and set status to `BLOCKED: MULTI_AGENT_COLLISION`.

3. **Human Arbiter Override:**
   - When evidence levels are equal and architecture is ambiguous, neither agent may guess. Both MUST halt and trigger `BLOCKED: ARCHITECTURAL_DECISION_REQUIRED`.
