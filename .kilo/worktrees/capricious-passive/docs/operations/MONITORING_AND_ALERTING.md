# YALIHAN OS — Monitoring and Alerting

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Parent:** OPERATIONS_MANUAL.md  
**Date:** 2026-06-28  

---

## 1. Overview

This document defines the complete monitoring and alerting framework for YALIHAN OS. It establishes what to monitor, how to measure, thresholds for alerts, and the escalation path when issues are detected.

Monitoring is the foundation of operational excellence — we cannot fix what we cannot see.

---

## 2. The Four Golden Signals

Every service in YALIHAN OS MUST expose the four golden signals:

| Signal | Description | Why It Matters |
|--------|-------------|---------------|
| **Latency** | Time to process requests | Slow is the new down |
| **Traffic** | System throughput | Demand vs capacity |
| **Errors** | Failure rate | Service health |
| **Saturation** | Resource utilization | Capacity planning |

### 2.1 Latency SLOs

| Service | p50 | p95 | p99 | Alert Threshold |
|---------|-----|-----|-----|---------------|
| API Gateway | < 50ms | < 200ms | < 500ms | p99 > 500ms |
| Agent Execution | < 5s | < 15s | < 30s | p99 > 30s |
| MCP Servers | < 100ms | < 300ms | < 500ms | p99 > 500ms |
| Vector DB | < 20ms | < 50ms | < 100ms | p99 > 100ms |
| Knowledge Base | < 200ms | < 1s | < 2s | p99 > 2s |

### 2.2 Traffic SLOs

| Service | Minimum | Expected | Maximum | Alert |
|---------|---------|----------|---------|-------|
| API Gateway | 100 RPS | 500 RPS | 2000 RPS | <50 RPS or >2500 RPS |
| Agent Queue | 10 tasks/min | 500 tasks/min | 2000 tasks/min | >3000 |
| MCP Requests | 50 RPS | 300 RPS | 1500 RPS | >2000 |

### 2.3 Error Rate SLOs

| Service | Error Budget | Alert Threshold | SLO |
|---------|-------------|----------------|-----|
| API Gateway | 0.1% | > 0.5% | 99.9% |
| Agent Execution | 1% | > 5% | 99% |
| MCP Servers | 0.5% | > 2% | 99.5% |
| Vector DB | 0.01% | > 0.1% | 99.99% |

### 2.4 Saturation Thresholds

| Resource | Warning | Critical | Emergency |
|----------|---------|----------|-----------|
| CPU | > 70% | > 85% | > 95% |
| Memory | > 75% | > 85% | > 95% |
| Disk | > 70% | > 85% | > 95% |
| Agent Slots | > 80% | > 90% | > 98% |
| Queue Depth | > 5000 | > 10000 | > 20000 |

---

## 3. Platform Health Metrics

### 3.1 Health Score Calculation

```
Platform Health Score = weighted_average([
  API Gateway Health     × 0.20,
  Agent Pool Health      × 0.25,
  MCP Infrastructure × 0.15,
  Vector DB Health       × 0.15,
  Queue Health           × 0.10,
  Memory Health          × 0.10,
  Token Budget Health    × 0.05,
])
```

### 3.2 Health Score Thresholds

| Score | Status | Meaning | Action |
|-------|--------|---------|--------|
| 95–100 | **Excellent** | All systems nominal | None |
| 85–94 | **Good** | Minor issues | Monitor |
| 70–84 | **Degraded** | Significant issues | Investigate |
| 50–69 | **Poor** | Major issues | Escalate |
| < 50 | **Critical** | System failure imminent | Immediate response |

### 3.3 Component Health Weights

| Component | Weight | Key Metrics |
|-----------|--------|-------------|
| API Gateway | 20% | Latency, Error rate, Availability |
| Agent Pool | 25% | Active agents, Task success, Queue depth |
| MCP Infrastructure | 15% | Connection health, Latency, Errors |
| Vector DB | 15% | Query latency, Index health, Replica lag |
| Queue | 10% | Depth, Processing rate, DLQ size |
| Memory | 10% | Usage, GC pressure, Cache hit rate |
| Token Budget | 5% | Daily usage vs budget, Cost rate |

---

## 4. Monitoring Dashboards

### 4.1 Executive Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│               YALIHAN OS — EXECUTIVE DASHBOARD                   │
│               Last Updated: {timestamp}                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  PLATFORM HEALTH          ┌──────────────────────────────────┐  │
│  ████████████████████░░░  │                                  │  │
│  87/100 — GOOD            │     SLA COMPLIANCE                │  │
│                          │     ● API: 99.97%                 │  │
│                          │     ● Agents: 99.85%              │  │
│                          │     ● MCP: 99.99%                  │  │
│  ACTIVE AGENTS           │     ● Vector DB: 100%             │  │
│  487 / 500               │                                  │  │
│  (97% utilization)        └──────────────────────────────────┘  │
│                                                                  │
│  TODAY'S METRICS        ┌──────────────────────────────────┐   │
│  ──────────────          │     TOP INCIDENTS (30 DAYS)       │   │
│  Requests: 1.2M          │     P0: 0  P1: 2  P2: 8  P3: 15  │   │
│  Tasks: 48,392           │                                  │   │
│  Errors: 0.03%           │     MTTR Average:                 │   │
│  Tokens: 842B / 10T      │     P0: 23min  P1: 1.2hr         │   │
│  Cost: $2,847            │                                  │   │
│                          └──────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Operations Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│               YALIHAN OS — OPERATIONS DASHBOARD                  │
├──────────────┬──────────────┬──────────────┬─────────────────────┤
│ API GATEWAY  │ AGENT POOL   │ MCP SERVERS │ VECTOR DB           │
│ ● Healthy    │ ● Healthy    │ ● Healthy   │ ● Healthy           │
│ p99: 187ms   │ 487 active   │ 15/15 up    │ p99: 42ms           │
│ Err: 0.02%   │ Queue: 234   │ Err: 0.01%  │ Replica lag: 12ms   │
├──────────────┴──────────────┴──────────────┴─────────────────────┤
│ ACTIVE ALERTS (3)                                                │
│ ● WARNING: Agent pool at 97% utilization                         │
│ ● WARNING: Token budget at 84% for today                         │
│ ● INFO: Scheduled maintenance in 4 hours                          │
├─────────────────────────────────────────────────────────────────┤
│ INCIDENT QUEUE                                                   │
│ Active: 0 P0  |  0 P1  |  1 P2  |  12 P3                       │
│ [View All Incidents]                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 4.3 Agent Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│ AGENT: yalihan-product-listing-agent-a1b2c3                      │
├─────────────────────────────────────────────────────────────────┤
│ STATUS: ● ACTIVE  │ TIER: Operational │ OFFICE: Product          │
├─────────────────────────────────────────────────────────────────┤
│ CAPABILITIES                                                     │
│ [●] listing_create  [●] listing_update  [●] photo_upload        │
│ [●] ai_description  [○] video_processing                        │
├─────────────────────────────────────────────────────────────────┤
│ METRICS (Last 24h)                                               │
│ Tasks: 1,847  │ Success: 99.4%  │ Avg Latency: 8.2s             │
│ Errors: 11    │ P95 Latency: 14s  │ P99 Latency: 22s           │
├─────────────────────────────────────────────────────────────────┤
│ RESOURCES                                                        │
│ Memory: 2.1GB/4GB  │ CPU: 34%  │ Tokens: 42B/500B daily        │
├─────────────────────────────────────────────────────────────────┤
│ RECENT TASKS                                                     │
│ 14:32:15  ● listing_update  │ 6.2s  │ Success                     │
│ 14:31:42  ● ai_description  │ 12.1s │ Success                     │
│ 14:30:58  ● listing_create   │ 8.8s  │ Success                     │
│ 14:29:33  ○ video_processing │ —     │ Not available for this tier │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Alert Configuration

### 5.1 Alert Rules

```yaml
alert_rules:
  # Platform-level alerts
  platform_health_score:
    metric: platform.health_score
    evaluation: average(5m)
    warning: < 85
    critical: < 70
    urgent: < 50
    for: [ops-lead, on-call]

  # Availability alerts
  api_availability:
    metric: api.gateway.availability
    evaluation: sum(1h) / count(1h)
    warning: < 99.5%
    critical: < 99.0%
    urgent: < 98.0%
    for: [on-call, ops-lead]

  # Latency alerts
  api_latency_p99:
    metric: api.gateway.latency.p99
    evaluation: average(5m)
    warning: > 500ms
    critical: > 1s
    urgent: > 5s
    for: [on-call]

  # Error rate alerts
  error_rate:
    metric: platform.errors.rate
    evaluation: average(5m)
    warning: > 0.5%
    critical: > 1%
    urgent: > 5%
    for: [on-call, ops-lead]

  # Agent alerts
  agent_pool_utilization:
    metric: agents.pool.utilization
    evaluation: average(5m)
    warning: > 85%
    critical: > 95%
    for: [ops-lead]

  agent_unhealthy:
    metric: agents.status
    evaluation: count
    filter: status=unhealthy
    warning: > 0
    critical: > 5
    for: [on-call]

  agent_dead:
    metric: agents.heartbeat
    evaluation: count
    filter: heartbeat=null
    critical: > 0
    for: [on-call, ops-lead]
    urgency: immediate

  # Queue alerts
  queue_depth:
    metric: queue.depth
    evaluation: max(5m)
    warning: > 5000
    critical: > 10000
    urgent: > 20000
    for: [ops-lead]

  dlq_size:
    metric: queue.dlq.size
    evaluation: max(5m)
    warning: > 100
    critical: > 500
    for: [ops-lead]

  # Token budget alerts
  token_budget_daily:
    metric: tokens.usage.daily
    evaluation: latest
    warning: > 80%
    critical: > 95%
    for: [ops-lead, finance]

  # Infrastructure alerts
  cpu_usage:
    metric: infrastructure.cpu.usage
    evaluation: average(5m)
    warning: > 70%
    critical: > 85%
    urgent: > 95%
    for: [on-call]

  memory_usage:
    metric: infrastructure.memory.usage
    evaluation: average(5m)
    warning: > 75%
    critical: > 85%
    urgent: > 95%
    for: [on-call]

  disk_usage:
    metric: infrastructure.disk.usage
    evaluation: average(5m)
    warning: > 70%
    critical: > 85%
    for: [ops-lead]

  # Vector DB alerts
  vector_db_latency:
    metric: vectordb.query.latency.p99
    evaluation: average(5m)
    warning: > 100ms
    critical: > 500ms
    for: [on-call]

  vector_db_replica_lag:
    metric: vectordb.replica.lag
    evaluation: max(5m)
    warning: > 100ms
    critical: > 1s
    for: [ops-lead]

  # MCP alerts
  mcp_server_down:
    metric: mcp.server.status
    evaluation: count
    filter: status=down
    critical: > 0
    for: [on-call, integration-lead]
```

### 5.2 Alert Routing

| Severity | Immediate Contact | Secondary Contact | Escalation |
|----------|-----------------|-------------------|------------|
| **Urgent** | PagerDuty → On-Call | Ops Lead | 5 min |
| **Critical** | PagerDuty → On-Call | Ops Lead | 15 min |
| **Warning** | Slack #ops-alerts | Ops team | None |
| **Info** | Slack #ops-info | None | None |

### 5.3 Alert Notification Channels

```yaml
notification_channels:
  pagerduty:
    purpose: Critical and Urgent alerts
    routing: On-Call rotation
    ack_required: Yes (15 min)
    escalation: Automatic after timeout

  slack_operations:
    channel: "#ops-alerts"
    purpose: Warning and above
    routing: Operations team
    ack_required: No
    escalation: Manual

  slack_info:
    channel: "#ops-info"
    purpose: Info alerts, summaries
    routing: Optional subscription
    ack_required: No

  email_operations:
    purpose: Alert digest, daily reports
    routing: Operations distribution list
    frequency: Daily digest

  executive_dashboard:
    purpose: Executive visibility
    routing: Auto-refresh dashboard
    alerts: P0/P1 only
```

---

## 6. Observability Stack

### 6.1 Metrics Collection

```
┌─────────────────────────────────────────────────────────────────┐
│ METRICS FLOW                                                      │
│                                                                   │
│   Services ──→ Prometheus ──→ Alertmanager ──→ PagerDuty/Slack   │
│       │              │              │                              │
│       │              ▼              ▼                              │
│       │         Grafana ◄──┘  (alert rules)                     │
│       │              │                                           │
│       ▼              ▼                                           │
│   Exporters ◄─────────┘                                          │
│   (node_exporter,                          ──→ Slack/Email      │
│    custom_metrics)                                               │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Logging Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│ LOGGING FLOW                                                      │
│                                                                   │
│   Application Logs                                               │
│        │                                                         │
│        ▼                                                         │
│   Fluentd/Bitfusion ──→ Elasticsearch ──→ Kibana                 │
│        │                                        │                │
│        ▼                                        ▼                │
│   LogStorage (S3)                      Dashboards               │
│   (long-term retention)                                           │
└─────────────────────────────────────────────────────────────────┘
```

### 6.3 Distributed Tracing

```
┌─────────────────────────────────────────────────────────────────┐
│ TRACE FLOW                                                        │
│                                                                   │
│   Request ──→ API Gateway ──→ Agent ──→ MCP ──→ Vector DB        │
│      │           │            │        │         │              │
│      └───────────┴────────────┴────────┴─────────┘              │
│                           │                                      │
│                           ▼                                      │
│                    Jaeger/Zipkin                                 │
│                           │                                      │
│                           ▼                                      │
│                    Trace Storage                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Custom Metrics

### 7.1 Agent Metrics

```yaml
agent_metrics:
  heartbeat:
    type: gauge
    description: Agent heartbeat indicator
    values: [0= dead, 1= alive]
    labels: [agent_id, office, tier]

  task_count:
    type: counter
    description: Total tasks processed
    labels: [agent_id, task_type, result]

  task_duration_seconds:
    type: histogram
    description: Task processing duration
    buckets: [1, 5, 10, 30, 60, 120, 300]
    labels: [agent_id, task_type]

  error_count:
    type: counter
    description: Task errors
    labels: [agent_id, error_type]

  token_usage:
    type: counter
    description: Token consumption
    labels: [agent_id, model, task_type]

  queue_depth:
    type: gauge
    description: Tasks waiting for agent
    labels: [agent_id]

  memory_usage_bytes:
    type: gauge
    description: Agent memory consumption
    labels: [agent_id]
```

### 7.2 Platform Metrics

```yaml
platform_metrics:
  health_score:
    type: gauge
    description: Overall platform health (0-100)
    labels: []

  active_agents:
    type: gauge
    description: Currently active agents
    labels: [office, tier]

  queue_total_depth:
    type: gauge
    description: Total tasks waiting across all queues
    labels: [priority]

  dlq_size:
    type: gauge
    description: Dead letter queue size
    labels: [queue_name]

  token_budget_remaining:
    type: gauge
    description: Remaining token budget percentage
    labels: [period, model_family]

  infrastructure_cost_hourly:
    type: gauge
    description: Current infrastructure cost rate
    labels: [service]

  mcp_connection_count:
    type: gauge
    description: Active MCP connections
    labels: [mcp_server]

  vectordb_query_latency:
    type: histogram
    description: Vector DB query latency
    buckets: [10, 25, 50, 100, 250, 500, 1000]
    labels: [collection]
```

---

## 8. SLO Monitoring

### 8.1 SLO Definition

```yaml
slos:
  api_availability:
    name: "API Gateway Availability"
    target: 99.9%
    window: 30d
    error_budget: 43.8 minutes/month
    metric: api.gateway.requests.total - api.gateway.errors

  api_latency:
    name: "API Gateway Latency"
    target: 99%
    window: 30d
    error_budget: 7.3 hours/month
    metric: api.gateway.latency.p99 < 500ms

  agent_execution:
    name: "Agent Task Success Rate"
    target: 99%
    window: 30d
    error_budget: 7.3 hours/month
    metric: tasks.success / tasks.total

  mcp_reliability:
    name: "MCP Server Availability"
    target: 99.5%
    window: 30d
    error_budget: 21.9 minutes/month
    metric: mcp.uptime

  vectordb_performance:
    name: "Vector DB Query Performance"
    target: 99.9%
    window: 30d
    error_budget: 43.8 minutes/month
    metric: vectordb.queries.slow / vectordb.queries.total < 0.1%
```

### 8.2 Error Budget Burn Rate

```yaml
error_budget_burn:
  # Fast burn: exhausting budget in 1 hour
  fast_burn:
    threshold: burn_rate > 90
    alert: URGENT
    action: Page on-call immediately

  # Medium burn: exhausting budget in 6 hours
  medium_burn:
    threshold: burn_rate > 15
    alert: WARNING
    action: Operations lead notification

  # Slow burn: exhausting budget in 30 days
  slow_burn:
    threshold: burn_rate > 1
    alert: INFO
    action: Track in weekly review
```

---

## 9. Monitoring Operations

### 9.1 Metric Collection Schedule

| Metric Type | Collection Frequency | Storage Retention |
|------------|---------------------|------------------|
| Heartbeat | 60 seconds | 90 days |
| Task metrics | Real-time | 30 days |
| Resource usage | 60 seconds | 90 days |
| Error rates | 60 seconds | 90 days |
| Latency histograms | 5 minutes | 30 days |
| Daily aggregates | Daily | 2 years |
| Monthly aggregates | Monthly | 7 years |

### 9.2 Dashboard Refresh

| Dashboard | Refresh Rate | Owner |
|----------|-------------|-------|
| Executive | 5 minutes | Automated |
| Operations | 30 seconds | Automated |
| Agent Detail | 30 seconds | Automated |
| Cost | 1 hour | Automated |

### 9.3 Alert Tuning

```yaml
alert_tuning:
  review_frequency: Monthly
  owner: Operations Lead
  process:
    1. Review alert volume and noise
    2. Identify chronic false positives
    3. Adjust thresholds if behavior changed
    4. Document threshold rationale
    5. Approve changes (Ops Lead)
```

---

**Document Owner:** Operations Office  
**Review Cycle:** Monthly  
**Related:** OPERATIONS_MANUAL.md, SLA_AND_SLO.md, INCIDENT_MANAGEMENT.md  

---

*End of Monitoring and Alerting*
