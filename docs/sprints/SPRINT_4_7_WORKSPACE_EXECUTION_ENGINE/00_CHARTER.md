# Sprint 4.7 — Workspace Execution Engine

**SAAB v7 APPROVED**
**Date:** 2026-07-04
**Mission:** Transform Workspace from Operational Cockpit into Operational Execution Engine.

---

## Board Mission

> Workspace shall not only display status. Workspace shall execute work.
> Every long-running operation must become an Execution.

---

## Why This Sprint Exists

Sprint 4.6 delivered the **Property Digital Twin Cockpit** — a read-only operational view.
Sprint 4.7 delivers the **Execution Engine** — the write/execute layer.

Today:
- Agents run synchronously inside Hermes event handlers
- One slow agent (e.g. PhotoAgent downloading from Drive) blocks the entire pipeline
- Failed agents cannot be retried — they must be re-triggered manually
- No visibility into "what is running right now"
- No replay capability — replaying means re-running the whole event chain

After Sprint 4.7:
- Every agent call becomes a `WorkspaceExecution` record
- Executions are queued, tracked, retried independently
- Replay restarts from the failed execution, not from the beginning
- The cockpit shows running/queued/failed executions in real time

---

## Execution Model

```
Workspace
    ↓ dispatch()
WorkspaceExecution (record created)
    ↓ queued to Redis
Queue Worker
    ↓ pops job
ProcessWorkspaceExecutionJob
    ↓ calls agent
Agent::handle()
    ↓ logs result
WorkspaceExecution (updated: succeeded/failed)
    ↓ emits event
Hermes → WorkspaceTimelineService → Cockpit
```

---

## Execution States

| State | Meaning |
|-------|---------|
| `queued` | Created, waiting for worker |
| `running` | Worker picked it up |
| `waiting` | Blocked by dependency |
| `retrying` | Automatic retry in progress |
| `succeeded` | Completed successfully |
| `failed` | Permanently failed |
| `cancelled` | Manually cancelled |
| `timed_out` | Exceeded timeout |

---

## P0 Deliverables

| # | Deliverable | File |
|---|-------------|------|
| 1 | `WorkspaceExecution` model | `app/Models/WorkspaceExecution.php` |
| 2 | Migration | `database/migrations/..._create_workspace_executions_table.php` |
| 3 | `WorkspaceExecutionService` | `app/Services/Workspace/WorkspaceExecutionService.php` |
| 4 | `ProcessWorkspaceExecutionJob` | `app/Jobs/Workspace/ProcessWorkspaceExecutionJob.php` |
| 5 | `ReplayService` | `app/Services/Workspace/ReplayService.php` |
| 6 | `RetryService` | `app/Services/Workspace/RetryService.php` |
| 7 | Execution API endpoints | `routes/admin.php` |
| 8 | Execution Monitor panel in cockpit | `cockpit.blade.php` |
| 9 | Tenant isolation enforcement | `WorkspaceExecutionPolicy` |

---

## Replay Rules

- Replay must restart from the **failed execution**, not from Workspace creation
- Replay must be **idempotent** — same execution can be replayed safely
- Replay creates a **new execution record** (never mutates the failed one)
- Replay is triggered via API: `POST /admin/workspace/{id}/executions/{execId}/replay`

---

## Retry Rules

- Automatic retry with configurable `max_attempts`
- Backoff strategy: exponential (configurable intervals)
- Each retry creates a **new execution record** (not mutation)
- `failure_reason` stored permanently on the failed record
- After `max_attempts`, state becomes `failed` permanently

---

## Background Workforce

- Agents MUST NOT execute inside HTTP requests
- `ProcessWorkspaceExecutionJob` is the only authorized agent execution path
- `AsyncHandlerDispatchJob` (existing) wraps Hermes handler calls
- Queue: `hermes` for AI agents, `workspace` for workspace operations

---

## Cockpit Execution Monitor

The cockpit's "Next Recommended Action" panel is extended to show:
- Running executions count
- Queued executions count
- Failed executions with replay button
- Last execution duration + timestamp
- Success rate (last 10 executions)

---

## KPI

| Metric | Target |
|--------|--------|
| Execution Success Rate | ≥ 80% |
| Replay Success Rate | ≥ 70% |
| Agent Reliability | ≥ 85% |

---

## Architecture

```
Workspace        (Business Aggregate — existing)
    │
    ├── hasMany → WorkspaceExecution  (NEW)
    │
    ├── Hermes  (Orchestrator — existing)
    │       │
    │       └── dispatches → ProcessWorkspaceExecutionJob  (NEW)
    │                              │
    │                              └── Agent::handle()
    │
    ├── Queue Workers (existing infrastructure)
    │
    └── Cockpit (Dashboard — existing)
            │
            └── Execution Monitor (NEW panel)
```

---

## Not In Scope

- Telegram integration
- New AI agents
- Drive sync
- Architecture changes to Hermes event bus
