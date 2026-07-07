# YSOS — Evidence Standard

> Every completed task produces evidence. Assertions without evidence are incomplete.

---

## Core Principle

> **Evidence is the artifact of completion.**

Saying "I fixed it" is not evidence.
Evidence is: what changed, what passed, what was verified.

---

## Evidence Definition

Evidence is a structured record of:

1. **Files Changed** — What was modified
2. **Tests** — What passed
3. **Build** — What compiled
4. **Verification** — How it was confirmed
5. **Known Debt** — What was not fixed

---

## Evidence Template

```markdown
## Evidence: [Task Name]

**Date:** YYYY-MM-DD
**Sprint:** X.Y
**Agent:** [Agent type]

### Files Changed
| File | Change |
|------|--------|
| [path] | [description] |

### Tests
```
[Test command output]
```

### Build
```
[Build command output]
```

### Verification
[Any manual verification steps]

### Known Debt
| Issue | Severity | Resolution |
|-------|----------|------------|
| [n] | [Severity] | [How to fix] |
```

---

## Evidence Collection Rules

### Rule 1: Every Task Produces Evidence

Every significant task produces evidence.
Minor edits (typos, formatting) are exceptions.
Feature work, bug fixes, refactors: always evidence.

### Rule 2: Evidence is Structured

Use the evidence template.
Free-form descriptions are not evidence.

### Rule 3: Evidence is Archived

Evidence lives in the sprint's 04_PROGRESS.md.
Not in chat history. Not in README files.
In the sprint progress document.

### Rule 4: Evidence Includes Commands

How was it verified?
Run a command and show the output.

---

## Evidence by Task Type

### Bug Fix
```
TASK: Fix ucfirst() TypeError on IlanDurumu enum
FILES: 3 blade files changed
TESTS: OwnerIlanCrudTest 12/15 pass (+3)
VERIFICATION: sab:dirty scan clean
DEBT: 3 pre-existing SQLite failures
```

### Feature Implementation
```
TASK: Implement edit() + update() methods
FILES: OwnerIlanController.php, routes/web.php
TESTS: 4 new owner CRUD tests pass
VERIFICATION: Routes verified
DEBT: None
```

### Refactoring
```
TASK: [Refactor name]
FILES: [Changed files]
TESTS: Full suite still passes
VERIFICATION: No regressions
DEBT: None
```

---

## Evidence Format in 04_PROGRESS.md

```markdown
### Task 1: [Name]
**Status:** ✅ COMPLETED
**Date:** YYYY-MM-DD

**Files Changed:**
- `path/to/file1.php` — [change]
- `path/to/file2.php` — [change]

**Tests:**
- `php artisan test --filter=TestName` → ✅ PASS

**Verification:**
```bash
php artisan sab:integrity-scan --dirty
# Result: 0 new violations
```

**Known Debt:** None
```

---

## Evidence for Quality Gates

### Gate 1: Tests
```
Command: php artisan test --filter=SPRINT_TEST
Result: X passed, Y failed
Status: ✅ PASS / ❌ FAIL
```

### Gate 2: Build
```
Command: php artisan route:cache
Result: [success/error]
Status: ✅ PASS / ❌ FAIL
```

### Gate 3: Integrity Scan
```
Command: php artisan sab:integrity-scan --dirty
Result: 0 new violations
Status: ✅ PASS / ❌ FAIL
```

---

## Evidence Storage

| Evidence Type | Storage Location |
|---------------|------------------|
| Sprint evidence | `docs/sprints/SPRINT_X_Y/04_PROGRESS.md` |
| Architecture decisions | `docs/sprints/SPRINT_X_Y/03_DECISIONS.md` |
| Test results | `docs/sprints/SPRINT_X_Y/05_TEST_REPORT.md` |
| Certification | `docs/sprints/SPRINT_X_Y/06_CERTIFICATION.md` |

---

## Evidence Verification

Evidence is verified by running the same commands the evidence claims passed.

```bash
# Verify evidence
php artisan test --filter=SPRINT_TEST
php artisan sab:integrity-scan --dirty
git diff --name-only
```

---

*Evidence is not optional. Evidence is the artifact.*
