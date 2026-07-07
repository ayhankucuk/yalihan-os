# YALIHAN OS

> Your entry point. Read in order.

---

## Quick Start

```
1. Read this file
2. Read docs/ysos/README.md
3. Read current Sprint Charter
4. Read previous Handoff
5. Read docs/ROADMAP.md
6. Begin implementation.
```

---

## What Is This Project?

YALIHAN OS is a **Digital Property Management Platform** for Bodrum luxury real estate.

It is also a **Product Company Framework** with three products:

| Product | Description |
|---------|-------------|
| **YALIHAN OS** | Digital Property Management Platform |
| **YSOS** | AI-assisted development operating system |
| **SAAB** | Engineering governance system |

---

## Architecture

```
SAAB v7        (Engineering Constitution — FROZEN)
     ↓
YSOS v1.x      (Engineering Operating System — ACTIVE)
     ↓
Hermes         (AI Workforce Orchestrator)
     ↓
Workspace      (Property Digital Twin)
     ↓
AI Workforce   (Agents)
     ↓
Business Automation
```

---

## Key Principles

```
Conversations   → are temporary
Repository     → is memory
YSOS           → is execution
SAAB           → is governance
Hermes         → is orchestration
Workspace      → is the business aggregate
Git             → is history
```

---

## How to Start Development

### Option 1: New AI Session

```bash
# 1. Read YSOS
cat docs/ysos/README.md

# 2. Read current sprint
cat docs/sprints/SPRINT_4_6_*/00_CHARTER.md

# 3. Read previous handoff
cat docs/sprints/SPRINT_4_5_*/07_HANDOFF.md

# 4. Read roadmap
cat docs/ROADMAP.md

# 5. Run health check
php artisan bekci:health --detailed

# 6. Begin implementation
```

### Option 2: New Feature

```bash
# 1. Read YSOS Sprint Lifecycle
cat docs/ysos/SPRINT_LIFECYCLE.md

# 2. Initialize new sprint
cp -r docs/sprints/SPRINT_TEMPLATE docs/sprints/SPRINT_X_Y/

# 3. Fill in 00_CHARTER.md
# 4. Get SAAB approval
# 5. Implement
# 6. Certify
# 7. Handoff
```

---

## Key Files

| File | Purpose |
|------|---------|
| `docs/ysos/README.md` | YSOS overview |
| `docs/ysos/CONSTITUTION.md` | Engineering Constitution |
| `docs/ysos/SPRINT_LIFECYCLE.md` | Sprint process |
| `docs/ROADMAP.md` | Project roadmap |
| `docs/PROGRESS-TRACKER.md` | Current status |
| `docs/BEKCI_CHANGELOG.md` | Session log |

---

## Sprint Workflow

```
YSOS Initialize
     ↓
SAAB Approval
     ↓
Implementation
     ↓
Evidence
     ↓
Certification
     ↓
Handoff
     ↓
Close
```

---

## What NOT to Do

- Do NOT use chat history as the primary source of truth
- Do NOT modify SAAB v7 or YSOS without Board Resolution
- Do NOT skip quality gates
- Do NOT add features outside sprint scope
- Do NOT bypass tenant isolation

---

## AI Agent Context

Every AI session should load context in this order:

```
1. docs/ysos/README.md
2. docs/ysos/OPERATING_SYSTEM.md
3. docs/ysos/SPRINT_LIFECYCLE.md
4. Current Sprint (00_CHARTER.md)
5. Previous Handoff (07_HANDOFF.md)
6. ROADMAP.md
7. Implementation
```

---

## Success Definition

A sprint succeeds when:

> At sprint end, the user can do something they couldn't do before —
> without opening multiple screens.

---

## Board Resolutions

| Resolution | Status |
|------------|--------|
| BR-2026-07-03-YSOS-001 | ✅ Approved |
| BR-2026-07-03-CONSTITUTION-001 | ✅ Approved |
| BO-2026-07-04-KPI-001 | ✅ Active |

---

*Conversations are optional. Repository is the source of truth.*
