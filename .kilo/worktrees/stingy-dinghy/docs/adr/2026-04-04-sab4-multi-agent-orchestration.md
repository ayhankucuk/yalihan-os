# ADR: SAB4 — Multi-Agent Orchestration Layer

**Date:** 2026-04-04
**Status:** Accepted
**Related:** [SAB3 — Decision Safety Layer](2026-06-16-sab3-decision-safety-layer.md), [SAB2 — Cortex Decision Engine](2026-06-15-sab2-cortex-decision-engine.md)

---

## Context

SAB2 established a single decision pipeline (Cortex → Guard → Bridge), and SAB3 added safety layers (rollback, suppression, explainability). However, the system remained **reactive**: it could only detect and decide, not learn or self-improve.

Key limitations:
- Single monolithic pipeline with no inter-component communication
- No learning from historical decisions (repeated suppressions, always-approved rules)
- No agent health visibility or failure isolation
- No mechanism for the system to suggest its own policy improvements

## Decision

Implement SAB4 as a **Multi-Agent Orchestration Layer** that transforms the single pipeline into five cooperating agents:

### Agent Architecture

| Agent | Role | Wraps |
|-------|------|-------|
| **CortexAgent** | Detection — scans and collects findings | `CortexFindingService` |
| **GovernanceAgent** | Decision — classifies findings into risk buckets | `GuardPolicyService` |
| **ExecutionAgent** | Action — creates proposals, queues decisions | `SabDecisionBridgeService` |
| **OptimizerAgent** | Learning — analyzes patterns, generates improvement suggestions | `OptimizerService` (new) |
| **WatcherAgent** | Coordinator — orchestrates pipeline, monitors health | All agents |

### Pipeline Flow

```
WatcherAgent.run()
  ├── CortexAgent → findings[] → FINDING_DETECTED events
  ├── GovernanceAgent → classified{} → DECISION_MADE events
  ├── ExecutionAgent → proposals/queue → ACTION_APPLIED/FAILED events
  └── OptimizerAgent → suggestions[] (non-blocking)
```

### Event Bus

7 governance events via Laravel's event system:
- `FindingDetected`, `DecisionMade`, `ActionApplied`, `ActionFailed`
- `RollbackExecuted`, `FindingSuppressed`, `OverrideApplied`

### Optimizer Learning Patterns

1. **Repeated suppression** (≥5x) → rule too sensitive
2. **Frequent rollback** (≥3x) → auto-run threshold too aggressive
3. **Always approved** (≥10x, 0 rejections) → upgrade to auto-run
4. **Repeat failures** (≥3x) → structural issue
5. **Frequent overrides** (≥3x same direction) → policy misalignment

### Safety Rule: Optimizer Cannot Self-Apply

All optimizer suggestions go through SAB approval:
```
Optimizer generates suggestion → pending
Admin approves/rejects → approved/rejected
If approved → applied (manual policy change)
```

## Database Changes

### Created: `agent_runs`
Tracks every agent execution: agent_name, agent_durumu, started_at, completed_at, duration_ms, input/output_summary, findings_count, decisions_count.

### Created: `optimizer_suggestions`
Learning output: suggestion_type, target_rule, current/suggested_value, reason, confidence, evidence, oneri_durumu (pending/approved/rejected/applied).

### Created: `agent_memory`
Persistent learning storage with upsert support: memory_type, memory_key, memory_value (JSON), agent_name. Unique on (agent_name, memory_key).

## New Files

**Agent Infrastructure:**
- `app/Agents/Contracts/AgentContract.php` — interface
- `app/Agents/BaseAgent.php` — lifecycle tracking, health, failure isolation
- `app/Agents/CortexAgent.php`
- `app/Agents/GovernanceAgent.php`
- `app/Agents/ExecutionAgent.php`
- `app/Agents/OptimizerAgent.php`
- `app/Agents/WatcherAgent.php`

**Events (7):**
- `app/Events/Governance/FindingDetected.php`
- `app/Events/Governance/DecisionMade.php`
- `app/Events/Governance/ActionApplied.php`
- `app/Events/Governance/ActionFailed.php`
- `app/Events/Governance/RollbackExecuted.php`
- `app/Events/Governance/FindingSuppressed.php`
- `app/Events/Governance/OverrideApplied.php`

**Models (3):**
- `app/Models/AgentRun.php`
- `app/Models/OptimizerSuggestion.php`
- `app/Models/AgentMemory.php`

**Services:**
- `app/Services/Intelligence/OptimizerService.php`

**UI:**
- `resources/views/admin/governance/intelligence-center.blade.php`

## Modified Files

- `app/Services/Intelligence/SabDecisionBridgeService.php` — extracted `executeClassified()` from `processBatch()`
- `app/Http/Controllers/Admin/DecisionEngineController.php` — scan() uses WatcherAgent, 3 new methods (intelligenceCenter, approveSuggestion, rejectSuggestion)
- `routes/admin.php` — 3 new SAB4 routes
- `app/Providers/EventServiceProvider.php` — 7 governance events registered

## New Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `intelligence-center` | AI Intelligence Center dashboard |
| POST | `suggestions/{suggestion}/approve` | Approve optimizer suggestion |
| POST | `suggestions/{suggestion}/reject` | Reject optimizer suggestion |

## Consequences

### Positive
- System evolves from reactive to proactive — learns from its own decisions
- Agent failure isolation — one agent failing doesn't crash the pipeline
- Full observability — every agent run tracked with duration, findings, decisions
- Optimizer suggestions are auditable and require human approval
- Event-driven architecture enables future extensions (new agents, listeners)

### Negative
- Additional DB writes per scan cycle (5 agent_runs records)
- Optimizer analysis requires sufficient historical data to generate meaningful suggestions
- WatcherAgent runs agents synchronously (not parallel) — acceptable for current scale

## Alternatives Considered

1. **External orchestration (n8n/Temporal):** Rejected — adds infrastructure complexity for an internal-only pipeline
2. **Async agent execution (queued jobs):** Rejected for now — synchronous is simpler and the pipeline completes in <1s
3. **Optimizer with auto-apply:** Rejected — violates SAB governance principles (no self-modification without approval)
4. **Separate agent microservices:** Rejected — monolithic approach is appropriate for current scale
