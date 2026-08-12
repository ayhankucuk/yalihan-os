# YDL v1 — Yield-Driven Loop Engine
## Architecture Charter

**Version:** 1.0
**Date:** 2026-08-12
**Status:** CHARTER — Implementation Not Started
**Owner:** Kilo Agent (YDL Orchestrator)
**Root Cause:** Sprint 4.15 certification loop exposed manual, error-prone memory update pattern.

---

## Problem Statement

### What We Did Manually in Sprint 4.15

After Sprint 4.14 certification, 4 memory files were updated manually:

```
docs/BEKCI_CHANGELOG.md      ← Oturum + Sprint özeti
memory/SESSION_NOTES.md      ← Agent oturum notu
memory/CHANGELOG_AGENT.md    ← AI agent değişiklik kaydı
docs/PROGRESS-TRACKER.md     ← Sprint tablosu + statü badge
```

This manual pattern has **three critical failure modes:**

1. **State Drift** — Human/agent updates files at different times → memory diverges
2. **Omission Risk** — Easy to forget one of the 4 files after a sprint
3. **Certification Loss** — No durable record of WHY a decision was made

### What Sprint 4.15 Actually Required (The Real Pattern)

```
CERTIFICATION EVIDENCE
  └── Evidence collected (test results, SAB violations)
  └── Decision made (PASS / BLOCKED / FAIL / N/A per gate)
  └── State snapshot generated

MEMORY UPDATE (the bottleneck)
  └── Update 4 files in correct order
  └── All files reference same canonical evidence
  └── Next sprint state derived from current

NEXT ACTION
  └── If all internal gates PASS + external blocked → YDL decides: parallel work OK
  └── If FAIL gates exist → YDL prioritizes fix
  └── If new capability ready → YDL proposes next sprint
```

---

## YDL Vision

> **YDL (Yield-Driven Loop Engine):** The AI OS that autonomously drives
> certification → memory → next-action cycles, replacing the 4-file manual
> update with a deterministic, auditable pipeline.

---

## YDL Core Design

### Three Engines

```
┌─────────────────────────────────────────────────────┐
│  YDL v1 Engine Stack                                │
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │  NextBestActionEngine                         │  │
│  │  "What should we do next?"                   │  │
│  │  Input: ProjectStateSnapshot + BlockerRegistry │  │
│  │  Output: Recommended next action + rationale  │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                            │
│  ┌──────────────────▼───────────────────────────┐  │
│  │  BlockerRegistry                           │  │
│  │  "What's blocked and why?"                │  │
│  │  Tracks: owner, reason, type, next_action   │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                            │
│  ┌──────────────────▼───────────────────────────┐  │
│  │  ProjectStateSnapshotEngine                 │  │
│  │  "What's the current system state?"         │  │
│  │  Runs: test queries, SAB scan, health check  │  │
│  └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Engine 1: ProjectStateSnapshotEngine

Collects deterministic evidence after each certification event.

**Trigger:** Automatic after test run, or manual `ydl:snapshot` command.

**Output:** `memory/ydl/snapshots/{sprint}-{timestamp}.json`

```json
{
  "snapshot_id": "snap-s4.15-20260812-1104",
  "sprint": "Sprint 4.15",
  "certification": {
    "total_tests": 83,
    "tests_passed": 83,
    "tests_failed": 0,
    "sab_violations_new": 0,
    "certification_score": "34/35 PASS",
    "gate_results": [
      {"gate": "G1", "result": "PASS", "evidence": "BW1-01"},
      {"gate": "G34", "result": "PASS", "evidence": "G34-01..G34-10"},
      {"gate": "G35", "result": "BLOCKED_EXTERNAL", "owner": "BOOKING_COM", "reason": "PARTNER_ONBOARDING"}
    ]
  },
  "sab": {
    "total_violations": 19,
    "new_violations": 0,
    "blocking_violations": 0
  },
  "memory_files": {
    "BEKCI_CHANGELOG.md": "updated",
    "SESSION_NOTES.md": "updated",
    "CHANGELOG_AGENT.md": "updated",
    "PROGRESS-TRACKER.md": "updated"
  },
  "generated_at": "2026-08-12T11:04:00+03:00"
}
```

**Evidence Sources (read-only queries):**
```php
// Tests
Artisan::call('test', [...]) → parse output → {passed, failed, duration}

// SAB
Artisan::call('sab:integrity-scan') → parse violations → {total, new, blocking}

// Health
Artisan::call('bekci:health') → parse output → {health_pct, issues[]}

// Gates
JSON file per sprint charter → gate_results[]
```

### Engine 2: BlockerRegistry

Tracks external dependencies that prevent full certification.

**Schema:**
```json
{
  "blockers": [
    {
      "id": "BLK-001",
      "gate": "G35",
      "sprint": "Sprint 4.15",
      "type": "EXTERNAL_DEPENDENCY",
      "owner": "BOOKING_COM",
      "reason": "PARTNER_ONBOARDING",
      "development_action": "DO_NOT_CONTINUE_BOOKING_CODE",
      "next_independent_action": "YDL_V1",
      "created_at": "2026-08-12T11:04:00+03:00",
      "last_updated": "2026-08-12T11:04:00+03:00",
      "status": "ACTIVE"
    }
  ]
}
```

**Blocker Types:**
| Type | Description | Dev Action |
|------|-------------|-----------|
| `EXTERNAL_DEPENDENCY` | Partner/3rd-party blocking | DO_NOT_CONTINUE_THIS_CODE |
| `INFRASTRUCTURE_ISSUE` | Pre-existing infra bug | P2_ESCALATE |
| `ARCHITECTURAL_GAP` | Missing capability | SPRINT_REQUIRED |
| `TEST_FLAKE` | Non-deterministic test | ISOLATE_AND_FIX |

**Decision Rules:**
```php
if ($blocker->type === 'EXTERNAL_DEPENDENCY') {
    // Independent work OK — continue
    return ['parallel_work' => true, 'recommended_sprint' => $nextBest];
}

if ($blocker->type === 'INFRASTRUCTURE_ISSUE') {
    // Assess if blocking production gates
    return ['parallel_work' => !$blocker->blocksProductionGates()];
}
```

### Engine 3: NextBestActionEngine

Given current state → recommended next action.

**Algorithm:**

```
1. Load latest ProjectStateSnapshot
2. Load BlockerRegistry
3. Classify all gates by result:
   - FAIL gates → P0 fixes (blocking)
   - BLOCKED_EXTERNAL → parallel work OK
   - PASS gates → compute coverage

4. If FAIL gates exist:
   → Recommend: highest-value FAIL gate fix
   → Output: specific task, files, test command

5. If all internal gates PASS + external blocked:
   → Check for highest-value independent next sprint
   → Output: next sprint charter draft

6. If all gates PASS:
   → Trigger: full memory update pipeline
   → Output: 4-file update evidence
```

**Decision Matrix:**

| State | Next Best Action |
|-------|----------------|
| FAIL gates exist | Fix highest-value FAIL gate |
| All internal PASS + external BLOCKED | Start next independent sprint |
| All gates PASS | Close sprint, update memory |
| Mixed (PASS + FAIL + BLOCKED) | Fix FAILs first, parallel if independent |

---

## Sprint 4.15 Live Test Case

### Current State (as of 2026-08-12T11:04)

```
Sprint 4.15
Engineering: COMPLETE
Tests: 83/83 PASS ✅
SAB: CLEAN ✅
Production Certification:
  G1-G33:   PASS
  G34:       PASS (FIX-3)
  G35:       BLOCKED_EXTERNAL
    owner:     BOOKING_COM
    reason:    PARTNER_ONBOARDING
    dev_action: DO_NOT_CONTINUE_BOOKING_CODE
```

### YDL Decision

```
INPUT:
  snapshot: snap-s4.15-20260812-1104
  blockers: [BLK-001 G35 EXTERNAL_DEPENDENCY ACTIVE]
  gate_results: 34 PASS, 1 BLOCKED_EXTERNAL, 0 FAIL

ANALYSIS:
  FAIL gates: 0 ✅
  Internal gates PASS: 34/34 ✅
  External blocked: 1 (G35)
  Parallel work OK: YES ✅

RECOMMENDATION:
  action: START_YDL_V1
  rationale: "All development gates complete. G35 requires Booking.com
             partner onboarding (external). Next highest-value
             independent work is YDL v1 itself."
  parallel_sprints: ["YDL v1"]
  blocked_sprints: ["Sprint 4.16 Booking Production Smoke"]
```

---

## YDL v1 Implementation Scope

### Phase 1: Foundation (This Charter)

- [ ] YDL data directory structure
- [ ] BlockerRegistry schema + CRUD operations
- [ ] `ydl:snapshot` Artisan command
- [ ] Manual trigger for Phase 1

### Phase 2: Memory Automation

- [ ] 4-file memory update from snapshot evidence
- [ ] Idempotent (re-run produces same result)
- [ ] Diff preview before write

### Phase 3: NextBestAction Engine

- [ ] Decision algorithm implementation
- [ ] Recommendation output format
- [ ] `/review ydl` command for human approval

### Phase 4: Closed-Loop Certification

- [ ] Automatic trigger after `php artisan test`
- [ ] Certificate event → snapshot → memory update → next action
- [ ] Dashboard widget: YDL status

---

## YDL v1 Data Structures

### File: `memory/ydl/snapshots/{sprint}-{timestamp}.json`

Canonical evidence for each certification event.

### File: `memory/ydl/blockers.json`

SSOT for all active and resolved blockers.

### File: `memory/ydl/recommendations/{date}.md`

Daily AI-generated recommendations with rationale.

### File: `memory/ydl/state/current.json`

Latest snapshot + blockers + recommendation. Read by agents at startup.

---

## Commands

```bash
# Manual snapshot
php artisan ydl:snapshot

# Show current state
php artisan ydl:state

# List blockers
php artisan ydl:blockers

# Resolve a blocker
php artisan ydl:blockers:resolve BLK-001 --reason="Booking.com partner activated"

# Get next action recommendation
php artisan ydl:next

# Preview memory update (dry run)
php artisan ydl:memory:update --dry-run

# Execute memory update
php artisan ydl:memory:update --confirm
```

---

## Integration with Existing Tools

| Tool | Integration |
|------|------------|
| `php artisan test` | YDL hooks into test events |
| `sab:integrity-scan` | Snapshot reads violations |
| `bekci:health` | Snapshot reads health % |
| Sprint charters | Gate definitions sourced from charter files |
| BEKCI_CHANGELOG.md | Memory update target #1 |
| SESSION_NOTES.md | Memory update target #2 |
| CHANGELOG_AGENT.md | Memory update target #3 |
| PROGRESS-TRACKER.md | Memory update target #4 |

---

## Non-Goals (v1)

- Not a project management tool (Linear/Jira replacement)
- Not an autonomous coding agent
- Not a reporting dashboard
- Not a CI/CD orchestrator

---

## First Sprint Candidate After YDL v1

```
Sprint 4.16: Reservation Core Foundation

Prerequisites:
  ✅ Sprint 4.15 internal gates complete (G34 CLOSED)
  ⏳ Booking.com onboarding (G35 — parallel, not blocking)
  ✅ YDL v1 engine operational

Scope:
  - Reservation state machine (DRAFT → CONFIRMED → CANCELLED → COMPLETED)
  - Canonical reservation model
  - Double-booking prevention
  - Integration with PropertyAvailability
```
