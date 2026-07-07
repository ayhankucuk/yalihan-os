# SAAB v7 — YALIHAN Sprint Architecture Blueprint

> **Version:** 7.0
> **Date:** 2026-07-03
> **Status:** ACTIVE
> **Part of:** YSOS (Yalıhan Sprint Operating System)
> **Supersedes:** SAB v6.x

---

## What is SAAB?

**SAAB** = Sprint Architecture & Approval Board

Within YSOS, SAAB is the **governance layer** that reviews and approves every sprint.

```
YSOS (Engineering Operating System)
├── SAAB v7     ← Governance Layer
├── Sprint Lifecycle
├── Quality Gates
├── Context Engineering
└── Evidence Standards
```

---

## SAAB's Role in YSOS

| YSOS Component | SAAB's Role |
|----------------|-------------|
| Sprint Lifecycle | Reviews and approves each stage |
| Quality Gates | Defines and enforces gates |
| Architecture Rules | Sets constraints |
| Context Engineering | Ensures knowledge transfer |
| Evidence | Validates completion |

---

## SAAB v7 Principles

### 1. Governance is Automated
SAAB rules are enforced by:
- `sab:integrity-scan` — AST-based code analysis
- `bekci:health` — Runtime health monitoring
- Git hooks — Pre-commit validation
- CI pipeline — Automated gate enforcement

### 2. Context is Structured
Not chat history. Not tribal knowledge.
Structured documents in the repository.

### 3. Sprint = Atomic Unit
A sprint is not a timebox.
It is a complete delivery unit with:
- Clear start and end
- Verifiable output
- Knowledge transfer

### 4. Quality Gates are Non-Negotiable
Failing gates block certification.
No exceptions without explicit approval.

---

## SAAB Rules (v7)

### Rule 1: Thin Controller
Controllers must never contain business logic.
All writes flow: Controller → Service → Repository → DB

### Rule 2: Tenant Isolation
Every database query must be tenant-scoped.
Cross-tenant access is blocked at the ORM level.

### Rule 3: Naming Authority
Domain model fields use Turkish canonical names.
Framework fields stay in English.

### Rule 4: No Silent Errors
Every catch block must log or rethrow.
Silent errors are violations.

### Rule 5: No Direct DB Writes
Raw DB writes are forbidden (except migrations).
All writes go through repositories.

### Rule 6: Zero Tolerance for Governance Bypass
Changes that bypass governance are blocked.
No exceptions without architectural review.

### Rule 7: Evidence Over Assertions
Every completed task produces evidence.
Assertions without evidence are incomplete.

### Rule 8: Sprint Boundary
Work outside sprint scope requires a new sprint.
No feature creep. No scope expansion.

### Rule 9: Repository as Memory
The repository is the single source of truth.
Chat history is disposable.

### Rule 10: Certification Required
No sprint is complete until SAAB certifies it.
Uncertified sprints are not closed.

---

## SAAB Quality Gates

Every sprint must pass these gates:

```
GATE 1: Tests
  Command: php artisan test --filter=SPRINT_TEST
  Expected: 100% pass

GATE 2: Build
  Command: php artisan route:cache && php artisan config:cache
  Expected: No errors

GATE 3: Integrity Scan
  Command: php artisan sab:integrity-scan --dirty
  Expected: 0 new violations

GATE 4: Tenant Isolation
  Command: php artisan test --filter=TenantIsolation
  Expected: All pass

GATE 5: Migration Safety
  Command: php artisan migrate:status
  Expected: All Ran
```

---

## SAAB Approval Process

For every sprint, SAAB reviews:

```
1. SCOPE: Is the sprint mission clear?
2. ARCHITECTURE: Are architecture rules respected?
3. TESTS: Are tests passing?
4. EVIDENCE: Is there proof of completion?
5. BOUNDARY: Is work within sprint scope?
```

### Approval Levels

| Level | Meaning | When |
|-------|---------|------|
| `✅ APPROVED` | Ready to implement | Scope clear, architecture sound |
| `⚠️ CONDITIONAL` | Approved with exceptions | Minor issues documented |
| `❌ REJECTED` | Not ready | Major issues must be fixed |

---

## SAAB and the Sprint Lifecycle

```
STEP 1: LOAD CONTEXT
  Read YSOS → Sprint Charter → Previous Handoff
         ↓
STEP 2: VALIDATE
  Check branch, migration, tests, blocking issues
         ↓
STEP 3: PRESENT TO SAAB ← SAAB Review
  Present: Mission, Scope, Risks, Plan
  SAAB: Approve / Request Changes / Reject
         ↓
STEP 4: IMPLEMENT (after approval)
  Only sprint-scope work
         ↓
STEP 5: EVIDENCE
  Every task produces: Files, Tests, Build
         ↓
STEP 6: CERTIFICATION
  Run all quality gates
  SAAB reviews evidence
         ↓
STEP 7: HANDOFF
  Knowledge transfer document
         ↓
STEP 8: CLOSE
  Update trackers, push commits
```

---

## SAAB Violations

### Critical (Blocks Certification)
- Tenant isolation breach
- Direct DB write in controller
- Silent error swallowing
- Governance bypass attempt

### High (Must Fix Before Close)
- New blocking violations in sab:scan
- Failed quality gates
- Missing test coverage

### Medium (Documented Debt)
- Pre-existing violations
- Known technical debt
- Minor architecture deviations

### Low (Acceptable with Documentation)
- Naming violations in non-DB code
- Minor style issues
- Informational warnings

---

## SAAB and Technical Debt

SAAB defines three categories of debt:

### Category 1: Acceptable Debt
- Pre-existing violations from before SAAB v7
- Known issues with migration dependencies
- Deferred items from previous sprints

**Action:** Document in handoff. No action required.

### Category 2: Technical Debt
- Architecture deviations introduced during sprint
- Unresolved violations in changed files
- Missing test coverage

**Action:** Document with severity. Fix in next sprint or backlog.

### Category 3: Governance Debt
- Repeated violations of the same rule
- Intentional bypasses without documentation
- Scope creep without approval

**Action:** SAAB review required. May block future sprints.

---

## SAAB v7 vs SAAB v6

| Aspect | v6 (SAB) | v7 (SAAB) |
|--------|-----------|------------|
| Scope | All code governance | Sprint governance |
| Integration | Standalone | Part of YSOS |
| Enforcement | Manual + AST | Automated + CI |
| Documentation | Scattered | Centralized in YSOS |
| Context | Tribal knowledge | Repository as memory |
| Automation | Partial | Full pipeline |

---

## SAAB v7 Commands

| Command | Purpose |
|---------|---------|
| `php artisan sab:integrity-scan` | Full codebase scan |
| `php artisan sab:integrity-scan --dirty` | Changed files only |
| `php artisan bekci:health` | Runtime health check |
| `php artisan bekci:health --detailed` | Detailed health report |
| `php artisan ysos:sprint:validate` | Run all quality gates |

---

## SAAB v7 Integration with YSOS

SAAB v7 is the governance layer of YSOS.

```
YSOS Sprint Lifecycle
├── Load Context          ← YSOS responsibility
├── Validate             ← SAAB validates state
├── Initialize           ← YSOS creates workspace
├── SAAB Approval       ← SAAB reviews scope
├── Implementation      ← SAAB enforces rules
├── Evidence           ← SAAB requires proof
├── Certification      ← SAAB certifies completion
├── Handoff            ← SAAB reviews knowledge transfer
└── Close             ← SAAB marks complete
```

---

## SAAB v7 Success Definition

SAAB v7 succeeds when:

> Every sprint is reviewed by SAAB before implementation,
> every quality gate is automated, and every completed
> sprint is certified before close — with zero reliance
> on chat history for governance decisions.

---

## Migration: SAAB v6 → SAAB v7

| From | To | Action |
|------|-----|--------|
| `docs/SAB.md` | `docs/ysos/SAAB_V7.md` | New file created |
| SAAB v6 rules | SAAB v7 rules | Integrated into YSOS |
| Scattered governance | Centralized in YSOS | Unified structure |
| Manual enforcement | Automated + CI | Enhanced |

---

## SAAB v7 Status

| Item | Status |
|------|--------|
| SAAB v7 created | ✅ 2026-07-03 |
| Integrated into YSOS | ✅ |
| Quality gates defined | ✅ |
| Sprint lifecycle aligned | ✅ |
| Evidence standards defined | ✅ |

---

*SAAB v7 — The governance layer of YSOS.*
*Every sprint is reviewed. Every gate is enforced. Every completion is certified.*
