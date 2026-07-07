# YSOS — Certification Standard

> A sprint is certified when all quality gates pass. No exceptions.

---

## Core Principle

> **Certification is the seal of quality. A sprint that is not certified is not complete.**

---

## Certification Definition

**Certification** is the formal confirmation that a sprint has:
- Completed all planned work
- Passed all quality gates
- Produced all required artifacts
- Transferred knowledge to the next sprint

---

## Certification Checklist

```
┌────────────────────────────────────────────────────────────┐
│              SPRINT CERTIFICATION CHECKLIST                  │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  □ Sprint scope complete                                    │
│  □ All tests pass (or known debt documented)               │
│  □ Build succeeds                                           │
│  □ 0 new SAB violations in changed files                    │
│  □ Tenant isolation verified                                │
│  □ All migrations ran                                       │
│  □ Sprint documents complete (00-07)                        │
│  □ Handoff document produced                                │
│  □ PROGRESS-TRACKER.md updated                             │
│  □ BEKCI_CHANGELOG.md updated                             │
│  □ All commits pushed                                       │
│                                                             │
│  OVERALL: CERTIFIED / NOT CERTIFIED                         │
│                                                             │
└────────────────────────────────────────────────────────────┘
```

---

## Certification Levels

### Level 1: CONDITIONAL PASS

Sprint completed with minor issues.

Criteria:
- All P0 work done
- All P1 work done (or documented as deferred)
- No new blocking violations
- P2 work optional
- 1-3 test failures that are pre-existing

Example: `Sprint 4.2 — Owner CRUD Certification`

---

### Level 2: FULL PASS

Sprint completed with no issues.

Criteria:
- All P0 + P1 work done
- 100% test pass
- 0 new violations
- All P2 work done (if planned)

---

### Level 3: EXCEPTIONAL PASS

Sprint exceeded expectations.

Criteria:
- All P0 + P1 + P2 work done
- 100% test pass
- 0 new violations
- Bonus work completed (out of scope but valuable)
- Architecture improved

---

## Certification Evidence

A certified sprint produces:

```
docs/sprints/SPRINT_X_Y/
├── 00_CHARTER.md        ← Defined before start
├── 01_CONTEXT.md        ← Analyzed before start
├── 02_TASKS.md         ← Planned before start
├── 03_DECISIONS.md    ← Documented during
├── 04_PROGRESS.md      ← Updated during + close
├── 05_TEST_REPORT.md  ← Generated at close
├── 06_CERTIFICATION.md ← Generated at close
└── 07_HANDOFF.md      ← Generated at close
```

---

## Certification Gate Results Template

```markdown
# Sprint X.Y — Certification

## Overall Status: CERTIFIED ✅ / NOT CERTIFIED ❌

## Gate Results

| Gate | Result | Details |
|------|--------|---------|
| GATE 1: Tests | ✅ PASS / ❌ FAIL | X passed, Y failed |
| GATE 2: Build | ✅ PASS / ❌ FAIL | [details] |
| GATE 3: Integrity | ✅ PASS / ❌ FAIL | 0 new violations |
| GATE 4: Tenant | ✅ PASS / ❌ FAIL | X tests passed |
| GATE 5: Migration | ✅ PASS / ❌ FAIL | All migrations Ran |

## Sprint Scope

### Completed
- [x] Task 1
- [x] Task 2

### Deferred
| Task | Reason | Priority |
|------|--------|----------|
| Task 3 | [reason] | P1 |

## Known Debt

| # | Debt | Severity | Resolution |
|---|------|----------|------------|
| 1 | [desc] | MEDIUM | [fix] |

## Verdict

[CERTIFIED / NOT CERTIFIED — reason]
```

---

## Certification Process

```
1. Run all quality gates
2. Document results in 06_CERTIFICATION.md
3. Update 04_PROGRESS.md with final status
4. Update PROGRESS-TRACKER.md
5. Update BEKCI_CHANGELOG.md
6. Generate 07_HANDOFF.md
7. Mark sprint as CLOSED
8. Push all commits
```

---

## When Certification Fails

If any gate fails:

1. **STOP** — Do not mark sprint as complete
2. **FIX** — Resolve the failing gate
3. **RETEST** — Run the gate again
4. **DOCUMENT** — Record any exceptions
5. **RETRY CERTIFICATION** — Run full certification process

---

## Certification Authority

The following can grant exceptions:

| Exception | Authority |
|-----------|-----------|
| Pre-existing test failures | Sprint owner |
| Known debt deferral | SAAB approval |
| Scope reduction | User approval |
| Timeline extension | User approval |

---

## Certification Examples

### Example 1: Conditional Pass

```
# Sprint 4.2 — Certification

## Overall Status: CONDITIONAL PASS ✅

## Gate Results
- Tests: ⚠️ 12/15 (3 pre-existing SQLite failures)
- Build: ✅ PASS
- Integrity: ✅ 0 new violations
- Tenant: ✅ PASS
- Migration: ✅ All Ran

## Known Debt
- KD-4.2-1: SQLite yazlik_details.deleted_at (MEDIUM) — Sprint 4.x backlog

## Verdict
CONDITIONAL PASS — 3 pre-existing failures documented, all sprint-scope work complete.
```

### Example 2: Full Pass

```
# Sprint 4.3 — Certification

## Overall Status: FULL PASS ✅

## Gate Results
- Tests: ✅ 20/20 pass
- Build: ✅ PASS
- Integrity: ✅ 0 new violations
- Tenant: ✅ PASS
- Migration: ✅ All Ran

## Verdict
FULL PASS — All gates cleared. Sprint 4.3 certified.
```

---

## Certification Stamps

| Stamp | Meaning |
|-------|---------|
| `✅ CERTIFIED` | All gates passed |
| `⚠️ CONDITIONAL` | Gates passed with documented exceptions |
| `❌ NOT CERTIFIED` | Gates failed, sprint incomplete |

---

## Certification History

All certifications are recorded in:

```
docs/sprints/
├── SPRINT_4_0/06_CERTIFICATION.md
├── SPRINT_4_1/06_CERTIFICATION.md
├── SPRINT_4_2/06_CERTIFICATION.md
└── ...
```

---

*Certification is the seal of quality. No exceptions.*
