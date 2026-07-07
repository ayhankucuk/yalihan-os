# YSOS — Handoff Standard

> How knowledge transfers between sprints and agents. No context is lost.

---

## Core Principle

> **The handoff document must prepare the next agent for success.**

Everything the next agent needs to know lives in the repository.
Nothing critical lives in chat history.

---

## Handoff Definition

A **Handoff** is the knowledge transfer document produced at sprint close.

It contains:
- What was done
- What was not done
- What the next sprint needs to know
- Key files changed
- Commands to verify
- Known debt

---

## Handoff Template

```markdown
# Sprint X.Y — [Name] Handoff

**Sprint:** X.Y
**Closed:** YYYY-MM-DD
**Status:** CLOSED ✅
**Next Sprint:** X.Y+1

---

## What Was Done

### Completed Tasks
- [ ] Task 1 ✅
- [ ] Task 2 ✅

### Completed Features
- [Feature description]

---

## What Was Not Done

### Deferred Tasks
| Task | Reason | Priority |
|------|--------|----------|
| [Task] | [Reason] | [Priority] |

---

## What the Next Sprint Needs to Know

### Critical Knowledge
[Any critical context the next agent must have]

### Architecture Decisions
[Any decisions that affect future work]

### Dependencies
[Any dependencies the next sprint needs]

---

## Key Files Changed

### Changed Files
| File | Change |
|------|--------|
| [path] | [description] |

### New Files
| File | Purpose |
|------|---------|
| [path] | [purpose] |

---

## Commands to Verify

```bash
# Sprint X.Y verification
php artisan test --filter=SPRINT_TEST
php artisan route:list --name=sprint.routes
php artisan sab:integrity-scan --dirty
git log --oneline -5
```

---

## Known Debt

| # | Debt | Severity | Resolution |
|---|------|----------|------------|
| [n] | [Description] | [Severity] | [How to fix] |

---

## Sprint Documents

```
docs/sprints/SPRINT_X_Y/
├── 00_CHARTER.md     ← Mission
├── 01_CONTEXT.md     ← How we got here
├── 02_TASKS.md       ← Task plan
├── 03_DECISIONS.md   ← Architecture decisions
├── 04_PROGRESS.md    ← Status
├── 05_TEST_REPORT.md ← Test results
├── 06_CERTIFICATION.md ← DoD
└── 07_HANDOFF.md    ← This file
```

---

*Next agent can continue from this document alone.*
```

---

## Handoff Rules

### Rule 1: No Surprises

The next agent should not discover any critical information only through chat history.
All critical knowledge must be in the handoff.

### Rule 2: Verify Commands

Every handoff includes commands the next agent can run to verify state.

### Rule 3: Document Known Debt

Issues not fixed in this sprint are documented with:
- Description
- Severity
- How to fix

### Rule 4: Update Before Closing

The handoff is generated at sprint close, not after.
All progress must be documented before the sprint is marked closed.

---

## Handoff Production

At sprint close, generate:

1. Update `04_PROGRESS.md` with final status
2. Generate `05_TEST_REPORT.md`
3. Generate `06_CERTIFICATION.md`
4. Complete `07_HANDOFF.md`
5. Update `PROGRESS-TRACKER.md`
6. Update `BEKCI_CHANGELOG.md`

---

## Handoff Verification

The next agent can verify handoff completeness by:

```bash
# Verify all sprint documents exist
ls docs/sprints/SPRINT_X_Y/

# Verify key files were changed
git diff --name-only PREVIOUS_COMMIT..SPRINT_COMMIT

# Verify tests
php artisan test --filter=SPRINT_TEST

# Verify architecture
php artisan sab:integrity-scan --dirty
```

---

## Handoff History

All handoffs are preserved in the repository:

```
docs/sprints/
├── SPRINT_1_0/
├── SPRINT_2_0/
├── SPRINT_3_0/
├── SPRINT_4_0/
├── SPRINT_4_1/
├── SPRINT_4_2/
└── ...
```

Each handoff is a snapshot of what was done and what comes next.

---

*Knowledge transfers through documents, not conversations.*
