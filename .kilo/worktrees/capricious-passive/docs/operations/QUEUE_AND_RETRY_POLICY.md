# YALIHAN OS — Queue and Retry Policy

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Parent:** OPERATIONS_MANUAL.md  
**Date:** 2026-06-28  

---

## 1. Overview

This document defines the queue management strategy, retry policies, dead letter handling, and priority management for YALIHAN OS. Every task in the system flows through these queues, and the policies herein ensure reliable, efficient, and fair task processing.

---

## 2. Queue Architecture

### 2.1 Queue Topology

```
┌─────────────────────────────────────────────────────────────────────┐
│                        QUEUE TOPOLOGY                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  TASK SUBMISSION                                                       │
│       │                                                                │
│       ▼                                                                │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │              PRIORITY ROUTER                                       │ │
│  │                                                                   │ │
│  │    P0 ──────► Critical Queue ──────► Immediate Processing         │ │
│  │    P1 ──────► High Queue ─────────► Fast Lane                    │ │
│  │    P2 ──────► Normal Queue ───────► Standard Processing          │ │
│  │    P3 ──────► Low Queue ──────────► Best Effort                 │ │
│  │                                                                   │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│       │                                                                │
│       ▼                                                                │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │              AGENT POOL                                            │ │
│  │                                                                   │ │
│  │    ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐              │ │
│  │    │ Agent 1 │ │ Agent 2 │ │ Agent 3 │ │ Agent N │              │ │
│  │    └─────────┘ └─────────┘ └─────────┘ └─────────┘              │ │
│  │                                                                   │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│       │                                                                │
│       ▼                                                                │
│  ┌─────────┐    ┌─────────┐    ┌─────────┐                          │
│  │ SUCCESS │    │ RETRY   │    │   DLQ   │                          │
│  │         │    │ QUEUE   │    │ (Dead   │                          │
│  │         │◄───│         │───►│ Letter) │                          │
│  └─────────┘    └─────────┘    └─────────┘                          │
│                                        │                             │
│                                        ▼                             │
│                              ┌─────────────────┐                     │
│                              │ DLQ PROCESSING   │                     │
│                              │ - Alert          │                     │
│                              │ - Investigate    │                     │
│                              │ - Reprocess/Abandon                     │
│                              └─────────────────┘                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Queue Definitions

| Queue | Purpose | Priority | Max Depth | SLA |
|-------|---------|----------|-----------|-----|
| `critical` | P0 tasks | 0 | 100 | Immediate |
| `high` | P1 tasks | 1 | 1,000 | < 15 min |
| `normal` | P2 tasks | 2 | 10,000 | < 1 hour |
| `low` | P3 tasks | 3 | 50,000 | Best effort |
| `retry` | Failed task retry | — | 5,000 | Varies |
| `dlq` | Dead letter tasks | — | Unlimited | Manual |

---

## 3. Task Lifecycle

```
TASK STATES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CREATED → QUEUED → PROCESSING → SUCCESS
              │            │
              │            ├──→ FAILED → RETRY (up to max)
              │            │                    │
              │            │              ▼
              │            │         MAX_RETRIES_EXCEEDED → DLQ
              │            │
              │            ├──→ TIMEOUT → RETRY
              │            │
              │            └──→ CANCELLED

DLQ → REPROCESSED → QUEUED (re-enter lifecycle)
     → ABANDONED (final)
```

---

## 4. Priority Management

### 4.1 Priority Levels

| Priority | Level | Description | Examples |
|----------|-------|-------------|----------|
| **P0** | 0 — Critical | Immediate processing required | System health, security incidents |
| **P1** | 1 — High | Fast lane, within SLA | User-critical features |
| **P2** | 2 — Normal | Standard processing | Regular tasks |
| **P3** | 3 — Low | Best effort, flexible | Analytics, maintenance |

### 4.2 Priority Inheritance

Tasks can inherit or escalate priority:

```yaml
priority_inheritance:
  security_task:
    base_priority: 2
    conditions:
      - "Contains PII → escalate to P1"
      - "Security flagged → escalate to P0"
      - "User VIP → escalate 1 level"

  sla_approaching:
    condition: "Task approaching SLA breach"
    action: "Escalate 1 level"
    max_priority: 1

  retry_escalation:
    retry_count: 3
    action: "Escalate 1 level"
    reason: "Repeated failures indicate higher priority"
```

### 4.3 Priority Configuration

```yaml
priority_config:
  default_priority: 2

  max_queue_depth:
    P0: 100
    P1: 1000
    P2: 10000
    P3: 50000

  processing_slots_per_agent:
    P0: 1      # Exclusive processing
    P1: 2
    P2: 4
    P3: unlimited

  head_of_line_protection:
    enabled: true
    max_tasks_ahead: 10  # Low priority never waits behind >10 high priority
```

---

## 5. Retry Policy

### 5.1 Retry Configuration

```yaml
retry_policy:
  max_attempts: 5
  base_delay: 1 second
  max_delay: 5 minutes
  backoff_strategy: "exponential_with_jitter"
  jitter_factor: 0.1

  retry_on:
    - error_code: "NETWORK_ERROR"
      retryable: true
    - error_code: "TIMEOUT"
      retryable: true
    - error_code: "SERVICE_UNAVAILABLE"
      retryable: true
    - error_code: "RATE_LIMITED"
      retryable: true
      delay: 30 seconds  # Override backoff

  do_not_retry:
    - error_code: "INVALID_INPUT"
      retryable: false
    - error_code: "UNAUTHORIZED"
      retryable: false
    - error_code: "FORBIDDEN"
      retryable: false
    - error_code: "NOT_FOUND"
      retryable: false
    - error_code: "ALREADY_PROCESSED"
      retryable: false
```

### 5.2 Backoff Calculation

```
delay = min(base_delay × 2^attempt + jitter, max_delay)

Example (base_delay=1s, max_delay=5min):
  Attempt 1: delay = min(1 × 2^0, 300) = 1s
  Attempt 2: delay = min(1 × 2^1, 300) = 2s
  Attempt 3: delay = min(1 × 2^2, 300) = 4s
  Attempt 4: delay = min(1 × 2^3, 300) = 8s
  Attempt 5: delay = min(1 × 2^4, 300) = 16s

With jitter (±10%):
  Attempt 3: delay = 4s × (0.9 to 1.1) = 3.6s to 4.4s
```

### 5.3 Retry Decision Matrix

| Error Type | Retry? | Delay | Notes |
|-----------|--------|-------|-------|
| **Network Timeout** | Yes | Exponential | Network transient |
| **Service Unavailable** | Yes | 30s min | Service issue |
| **Rate Limited** | Yes | Per Retry-After | Respect server |
| **Auth Expired** | Yes | 1s | Refresh and retry |
| **Invalid Input** | No | — | Fix required |
| **Permission Denied** | No | — | Access issue |
| **Resource Exhausted** | No | — | Capacity problem |
| **Unknown Error** | Yes | Max delay | Log for analysis |

---

## 6. Dead Letter Queue (DLQ)

### 6.1 DLQ Entry Criteria

Tasks are sent to DLQ when:

1. **Max retries exceeded**: `retry_count >= max_attempts`
2. **Non-retryable error**: Permanent failure
3. **Task timeout**: `processing_time > task_timeout`
4. **Resource unavailable**: Required resource down for extended period
5. **Manual intervention**: Operator explicitly DLQ'd

### 6.2 DLQ Entry Format

```json
{
  "dlq_entry_id": "uuid",
  "original_task_id": "uuid",
  "queue": "high",
  "priority": 1,
  "entry_reason": "MAX_RETRIES_EXCEEDED",
  "error_code": "SERVICE_UNAVAILABLE",
  "error_message": "MCP server unreachable after 5 attempts",
  "retry_count": 5,
  "first_attempt": "2026-06-28T10:00:00Z",
  "last_attempt": "2026-06-28T10:15:00Z",
  "total_processing_time": "15 minutes",
  "task_payload": { ... },
  "task_metadata": {
    "agent_id": "agent-123",
    "office": "product",
    "owner": "user-456"
  },
  "dlq_timestamp": "2026-06-28T10:15:30Z",
  "status": "PENDING_REVIEW"
}
```

### 6.3 DLQ Processing

```yaml
dlq_processing:
  alert_on_entry: true
  alert_threshold: 10 items/hour

  processing_options:
    reprocess:
      description: "Re-queue task for processing"
      conditions:
        - "Root cause resolved"
        - "Manual approval obtained"
      priority_preservation: false  # Can change priority

    abandon:
      description: "Mark task as permanently failed"
      conditions:
        - "Task impossible to complete"
        - "Data no longer valid"
        - "Business decision"
      notification: ["owner", "operations"]

    extract_and_retry:
      description: "Extract sub-tasks and retry"
      conditions:
        - "Partial processing completed"
        - "Remaining work is independent"
```

---

## 7. Timeout Configuration

### 7.1 Timeout Types

| Timeout | Default | Max | Action on Expiry |
|---------|---------|-----|------------------|
| **Task Visibility** | 30s | 5 min | Return to queue |
| **Task Processing** | 5 min | 30 min | Cancel + retry |
| **Task Total** | 60 min | 4 hours | Move to DLQ |
| **Agent Heartbeat** | 60s | 5 min | Mark agent unhealthy |
| **MCP Call** | 30s | 2 min | Cancel + retry |

### 7.2 Timeout Configuration

```yaml
timeouts:
  task_visibility:
    default: 30 seconds
    per_priority:
      P0: 5 seconds
      P1: 15 seconds
      P2: 30 seconds
      P3: 60 seconds

  task_processing:
    default: 5 minutes
    per_task_type:
      listing_create: 2 minutes
      listing_update: 1 minute
      ai_description: 10 minutes
      photo_upload: 5 minutes
      bulk_operation: 30 minutes

  task_total_lifetime:
    default: 60 minutes
    max: 4 hours
    dq_on_expiry: true

  mcp_call:
    default: 30 seconds
    max: 2 minutes
    retry_on_timeout: true
```

---

## 8. Queue Monitoring

### 8.1 Key Queue Metrics

| Metric | Description | Alert Threshold |
|--------|-------------|----------------|
| `queue.depth` | Current items in queue | > 10,000 |
| `queue.enqueue_rate` | Items added per second | > 100/sec |
| `queue.dequeue_rate` | Items processed per second | < 10/sec |
| `queue.processing_time` | Average time in queue | > SLA threshold |
| `queue.dlq.size` | Dead letter queue size | > 100 |
| `queue.dlq.growth_rate` | DLQ growth per hour | > 10/hour |
| `queue.oldest_item_age` | Time of oldest unprocessed item | > 30 minutes |

### 8.2 Queue Health Rules

```yaml
queue_health:
  healthy:
    depth: < 1000
    dequeue_rate: > 50/sec
    dlq_size: < 10
    oldest_item_age: < 5 minutes

  degraded:
    depth: 1000-5000
    dequeue_rate: 10-50/sec
    dlq_size: 10-100
    oldest_item_age: 5-30 minutes

  unhealthy:
    depth: > 5000
    dequeue_rate: < 10/sec
    dlq_size: > 100
    oldest_item_age: > 30 minutes
```

---

## 9. Task Expiration

### 9.1 Expiration Rules

| Task Age | Action | Reason |
|---------|--------|--------|
| < 1 hour | Normal processing | Fresh task |
| 1–4 hours | Warning logged | Getting old |
| 4–8 hours | Escalate priority | SLA approaching |
| 8–24 hours | Manual review required | Stale task |
| > 24 hours | Auto-DLQ | Expired |

### 9.2 Expiration Configuration

```yaml
task_expiration:
  fresh_threshold: 1 hour
  stale_threshold: 4 hours
  critical_threshold: 8 hours
  expired_threshold: 24 hours

  actions:
    stale:
      log_warning: true
      notify_owner: false
      escalate_priority: true

    critical:
      log_warning: true
      notify_owner: true
      escalate_to_p1: true
      operations_aware: true

    expired:
      move_to_dlq: true
      notify_owner: true
      notify_operations: true
      preserve_for_analysis: true
```

---

## 10. Load Shedding

### 10.1 Shedding Triggers

```yaml
load_shedding:
  enabled: true

  triggers:
    cpu_usage: > 90%
    memory_usage: > 90%
    queue_depth: > 20000
    error_rate: > 5%

  shedding_strategy:
    # Drop lowest priority first
    order:
      - P3 tasks (best effort)
      - P2 tasks older than 1 hour
      - Non-critical background tasks

  preserved:
    # Never shed these
    - P0 tasks
    - P1 tasks
    - User-initiated synchronous requests
    - Security-related tasks

  shed_actions:
    return_503: true
    notify_caller: true
    log_shedding_event: true
    track_in_metrics: true
```

---

**Document Owner:** Operations Office  
**Review Cycle:** Monthly  
**Related:** OPERATIONS_MANUAL.md, MONITORING_AND_ALERTING.md, INCIDENT_MANAGEMENT.md  

---

*End of Queue and Retry Policy*
