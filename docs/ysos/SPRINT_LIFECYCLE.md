# YSOS — Sprint Lifecycle

> Every sprint follows the same stages. No exceptions.

---

## Sprint Definition

A **Sprint** is an atomic unit of delivery with:
- A clear mission
- A defined scope
- A start and end
- A verifiable output
- A handoff document

A sprint is not a timebox. It is a delivery unit.

---

## Sprint Stages

```
┌────────────────────────────────────────────────────────┐
│                   SPRINT LIFECYCLE                     │
├────────────────────────────────────────────────────────┤
│                                                        │
│  STEP 1: LOAD CONTEXT                                  │
│  Read YSOS → Sprint Charter → Handoff → Context        │
│                                                        │
│  STEP 2: VALIDATE                                     │
│  Check branch, migration, tests, blocking issues       │
│  If inconsistencies: STOP and explain                │
│                                                        │
│  STEP 3: INITIALIZE SPRINT                            │
│  Produce: Summary, Architecture, Progress, Risks     │
│  Do NOT implement anything yet                        │
│                                                        │
│  STEP 4: SAAB APPROVAL                                │
│  Present sprint plan to SAAB for approval             │
│  Priority: P0 → P1 → P2                              │
│  No implementation before approval                    │
│                                                        │
│  STEP 5: IMPLEMENTATION                               │
│  Only implement sprint-scope work                    │
│  Follow: Thin Controllers → Services → Repositories   │
│  Never redesign architecture                          │
│                                                        │
│  STEP 6: EVIDENCE                                     │
│  Every task produces: Files Changed, Tests, Build    │
│                                                        │
│  STEP 7: CERTIFICATION                                │
│  Run quality gates                                    │
│  All gates pass: Sprint certified                     │
│                                                        │
│  STEP 8: HANDOFF                                     │
│  Generate handoff document                            │
│  Update: ROADMAP, PROGRESS-TRACKER, CHANGELOG        │
│                                                        │
│  STEP 9: CLOSE                                        │
│  Mark sprint closed                                   │
│  Push commits                                         │
│  Begin next sprint                                    │
│                                                        │
└────────────────────────────────────────────────────────┘
```

---

## Stage 1: Load Context

**Read in this order:**

1. `docs/ysos/README.md` — YSOS overview
2. `docs/ysos/CONTEXT_ENGINEERING.md` — Context loading order
3. `docs/ysos/ARCHITECTURE_RULES.md` — Architecture constraints
4. `docs/ysos/AI_AGENT_RULES.md` — Agent behavior rules
5. `docs/ysos/QUALITY_GATES.md` — Verification checklist
6. `docs/sprints/SPRINT_X_Y/00_CHARTER.md` — Current sprint charter
7. `docs/sprints/SPRINT_X_Y/07_HANDOFF.md` — Previous sprint handoff (if exists)
8. `docs/PROGRESS-TRACKER.md` — Overall project state

**Do NOT read previous conversations.**

---

## Stage 2: Validate

Before any implementation, verify:

| Check | Command | Expected |
|-------|---------|----------|
| Current branch | `git branch` | feature/sprint-X-Y or main |
| Migration status | `php artisan migrate:status` | All Ran |
| Test baseline | `php artisan test --filter=SPRINT_TEST` | Known baseline |
| Blocking issues | Manual review | None |
| Git status | `git status` | Clean or known |

**If inconsistencies exist: STOP. Explain them. Resolve before continuing.**

---

## Stage 3: Initialize Sprint

Produce the Sprint Workspace:

- **Sprint Summary** — Mission, scope, boundaries
- **Architecture Summary** — What exists, what changes
- **Current Progress** — Where we are
- **Remaining Tasks** — What needs to be done
- **Blocking Issues** — What's stopping us
- **Risk Assessment** — What could go wrong
- **Dependencies** — What we need
- **Implementation Order** — How we'll work

**Do NOT implement anything yet. The sprint is not approved.**

---

## Stage 4: SAAB Approval

Present to SAAB (internal governance review):

```
SPRINT X.Y — NAME
Status: PENDING APPROVAL

Mission:
[One sentence describing what this sprint delivers]

Scope:
- In: [What's included]
- Out: [What's explicitly excluded]

P0 (Must Have):
- [ ] ...

P1 (Should Have):
- [ ] ...

P2 (Nice to Have):
- [ ] ...

Definition of Done:
- [ ] ...
- [ ] ...

Approval Required Before Implementation
```

SAAB approval means:
- Scope is clear
- Architecture is sound
- Quality gates are defined
- Risks are known

---

## Stage 5: Implementation

**Only sprint scope. Nothing else.**

Rules during implementation:
- Never redesign architecture
- Never refactor unrelated modules
- Never add features outside sprint scope
- Follow thin-controller → service → repository pattern
- Document every decision
- Produce evidence for every task

---

## Stage 6: Evidence

Every completed task produces:

```
TASK: [Name]
Files Changed: [List]
Tests: [Results]
Build: [Status]
Evidence: [What was verified]
Known Debt: [Issues discovered but not fixed]
```

Evidence is not optional. Evidence is the artifact.

---

## Stage 7: Certification

Run all quality gates:

```
GATE 1: Tests
  php artisan test --filter=SPRINT_TEST
  Expected: 100% pass

GATE 2: Build
  php artisan route:cache
  php artisan config:cache
  Expected: No errors

GATE 3: Integrity Scan
  php artisan sab:integrity-scan --dirty
  Expected: 0 new violations

GATE 4: Tenant Isolation
  Verified by tests

GATE 5: Migration Safety
  php artisan migrate:status
  Expected: All Ran
```

All gates pass → Sprint is **CERTIFIED**
Any gate fails → Sprint is **NOT CERTIFIED**

---

## Stage 8: Handoff

Generate handoff package:

```
docs/sprints/SPRINT_X_Y/
├── 07_HANDOFF.md      ← Generated here
├── 04_PROGRESS.md     ← Updated
├── 05_TEST_REPORT.md  ← Generated here
└── 06_CERTIFICATION.md ← Generated here
```

Handoff contains:
- What was done
- What was not done
- What the next sprint needs to know
- Key files changed
- Commands to verify

---

## Stage 9: Close

Mark sprint closed:

1. Update `docs/PROGRESS-TRACKER.md`
2. Update `docs/BEKCI_CHANGELOG.md`
3. Update `docs/ROADMAP.md` (if milestones changed)
4. Push all commits
5. Begin next sprint

---

## Sprint Template

Every sprint follows the same template structure:

```
docs/sprints/
SPRINT_X_Y/
├── 00_CHARTER.md        ← Define before start
├── 01_CONTEXT.md        ← Analyze before start
├── 02_TASKS.md         ← Plan before start
├── 03_DECISIONS.md    ← Document during
├── 04_PROGRESS.md      ← Update during + close
├── 05_TEST_REPORT.md  ← Generate at close
├── 06_CERTIFICATION.md ← Generate at close
└── 07_HANDOFF.md      ← Generate at close
```

---

*Every sprint follows the same stages. No exceptions.*
