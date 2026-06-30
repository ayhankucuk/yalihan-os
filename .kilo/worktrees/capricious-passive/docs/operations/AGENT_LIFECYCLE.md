# YALIHAN OS — Agent Lifecycle Management

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Parent:** OPERATIONS_MANUAL.md  
**Date:** 2026-06-28  

---

## 1. Overview

This document defines the complete lifecycle of every digital worker (agent) in the YALIHAN OS platform. Every agent, regardless of tier or function, MUST follow this lifecycle. No exceptions.

The lifecycle ensures:
- Full traceability from creation to retirement
- Human oversight at critical transitions
- Audit compliance
- Deterministic state management

---

## 2. Lifecycle Stages

```
┌──────────────────────────────────────────────────────────────────────┐
│                      AGENT LIFECYCLE STATES                          │
├────────────┬────────────┬────────────┬────────────┬──────────────────┤
│   CREATE   │  REGISTER  │  ACTIVATE  │   MONITOR  │    UPGRADE       │
│            │            │            │            │                  │
│  Request   │  Identity  │  Onboard   │  Ongoing   │  Capability      │
│  Design    │  Catalog   │  Policy    │  Health    │  Enhancement     │
│  Approval  │  Assign    │  First     │  Metrics   │                  │
├────────────┼────────────┼────────────┼────────────┼──────────────────┤
│  SUSPEND   │  ARCHIVE   │  RETIRE    │            │                  │
│            │            │            │            │                  │
│  Emergency │  Long-term │ Permanent  │            │                  │
│  Policy    │  Inactive  │ Shutdown   │            │                  │
│  Violation │  Preserve  │ Delete     │            │                  │
└────────────┴────────────┴────────────┴────────────┘──────────────────┘
```

---

## 3. Stage 1: CREATE

### 3.1 Preconditions

- Business justification documented
- Capability requirements defined
- Resource budget approved
- Human owner assigned

### 3.2 Creation Request Template

```yaml
agent_creation_request:
  requester_id: string          # Human who requested
  requester_office: string      # Business/Product/Research/etc.
  justification: string         # Why this agent is needed
  tier: strategic|tactical|operational|limited
  capabilities:
    - capability: string
      mcp_required: [string]
      permissions: [string]
  estimated_daily_tasks: number
  owner_id: string              # Human owner (accountable)
  budget_monthly_usd: number
  compliance_classification: public|internal|confidential|restricted
  data_retention_days: number
  created_at: ISO8601
```

### 3.3 Approval Workflow

```
Request → Capability Review → Resource Check → Budget Approval → Create
   │            │                   │             │           │
  User     Architecture         Operations     Finance    System
           Office               Lead          Office    (Automated)
```

### 3.4 Output

- `agent_id`: Unique identifier (format: `yalihan-{office}-{role}-{uuid}`)
- Initial capability manifest
- Resource allocation record

---

## 4. Stage 2: REGISTER

### 4.1 Registration Requirements

Every agent MUST be registered in the Agent Registry before activation:

```json
{
  "agent_id": "yalihan-product-listing-agent-a1b2c3",
  "office": "product",
  "tier": "operational",
  "capabilities": [
    "listing_create",
    "listing_update",
    "photo_upload",
    "ai_description_generation"
  ],
  "mcp_scope": [
    "mcp-listing-service",
    "mcp-photo-service"
  ],
  "owner_id": "user-uuid-here",
  "compliance_level": "confidential",
  "created_at": "2026-06-28T09:00:00Z",
  "registered_at": "2026-06-28T09:15:00Z",
  "status": "registered",
  "version": "1.0.0"
}
```

### 4.2 Registry Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `agent_id` | UUID | Yes | Unique identifier |
| `office` | Enum | Yes | `product\|research\|integration\|knowledge\|operations` |
| `tier` | Enum | Yes | `strategic\|tactical\|operational\|limited` |
| `capabilities` | Array | Yes | List of capability strings |
| `mcp_scope` | Array | Yes | Authorized MCP servers |
| `owner_id` | UUID | Yes | Human accountable owner |
| `compliance_level` | Enum | Yes | Data classification |
| `status` | Enum | Yes | Current state |
| `version` | String | Yes | Semantic version |

### 4.3 Registration Validation

- [ ] Agent ID is unique
- [ ] All required MCP servers are available
- [ ] Owner exists and has appropriate permissions
- [ ] Capability names are validated against approved list
- [ ] Compliance level is appropriate for data access

---

## 5. Stage 3: ASSIGN IDENTITY

### 5.1 Identity Components

Each agent receives a complete identity package:

```yaml
identity_package:
  agent_id: string                    # From registration
  display_name: string                # Human-readable name
  role_description: string           # What this agent does
  office: string                     # Parent office
  tier: string                       # Authority level
  permissions:                       # Specific permissions
    - resource: string
      actions: [read|write|execute|delete]
  mcp_bindings:                     # MCP server access
    - mcp_server: string
      capabilities: [string]
  communication_channels:            # How to reach this agent
    - channel: webhook|queue|event
      endpoint: string
  owner:                             # Human accountability
    user_id: string
    name: string
    email: string
    role: string
  metadata:
    created_at: ISO8601
    created_by: string
    purpose: string
```

### 5.2 Identity Assignment Rules

| Tier | Authority | Requires Approval For | Auto-Reject |
|------|-----------|----------------------|-------------|
| **Strategic** | Full autonomous | Nothing | Nothing |
| **Tactical** | High-risk actions | Write to DB, Delete | Prompts injection |
| **Operational** | Routine tasks | All writes, All deletes | Silent execution |
| **Limited** | Simple tasks | All actions | Suspicious input |

---

## 6. Stage 4: ACTIVATE

### 6.1 Activation Checklist

```
ACTIVATION CHECKLIST FOR: {agent_id}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Pre-Flight:
  [ ] Agent registry entry verified
  [ ] Identity package loaded
  [ ] MCP connections established
  [ ] Required services reachable
  [ ] Memory initialized
  [ ] First-task warming complete

Security:
  [ ] Authentication token valid
  [ ] Authorization policies loaded
  [ ] Rate limits configured
  [ ] Audit logging enabled

Monitoring:
  [ ] Health check registered
  [ ] Metrics emission started
  [ ] Alert rules applied
  [ ] Owner notification sent

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ACTIVATION AUTHORIZED BY: {human_id}
ACTIVATION TIME: {ISO8601}
```

### 6.2 First-Task Warming

After activation, each agent must complete a warming task:

```
Warming Task Sequence:
1. Self-health-check (report status to monitor)
2. Capability inventory (confirm all capabilities online)
3. Dependency check (verify MCP connectivity)
4. Permission validation (test access to required resources)
5. First-log entry (emit startup log with metadata)

Result: ACTIVE or ACTIVATION_FAILED
```

### 6.3 Activation Failure Handling

| Failure Type | Action | Notification |
|-------------|--------|-------------|
| MCP unreachable | Retry 3x with backoff | Owner + Ops |
| Permission denied | Block activation | Owner + Ops Lead |
| Resource unavailable | Queue for retry | Ops |
| Security policy fail | Block + Security review | Security Officer |

---

## 7. Stage 5: MONITOR

### 7.1 Monitoring Metrics

Every active agent MUST emit:

| Metric | Frequency | Threshold | Alert |
|--------|-----------|-----------|-------|
| `agent.heartbeat` | 60s | — | No beat in 5min = DEAD |
| `agent.task_count` | 1min | — | Abnormal spike |
| `agent.error_rate` | 1min | > 5% | Warning |
| `agent.latency_p95` | 5min | > SLA | Warning |
| `agent.token_usage` | 5min | > daily budget | Warning |
| `agent.memory_usage` | 1min | > 90% | Critical |
| `agent.queue_depth` | 1min | > 100 | Warning |

### 7.2 Health Status Codes

| Code | Status | Meaning | Action |
|------|--------|---------|--------|
| `HEALTHY` | Green | All systems nominal | None |
| `DEGRADED` | Yellow | Some issues detected | Investigate |
| `UNHEALTHY` | Red | Major issues | Escalate |
| `DEAD` | Gray | No heartbeat | Immediate response |
| `MAINTENANCE` | Blue | Scheduled downtime | Planned |
| `SUSPENDED` | Orange | Manually stopped | Human review |

### 7.3 Monitoring Schedule

```
REAL-TIME MONITORING (Always On):
├── Heartbeat check: every 60 seconds
├── Health metric collection: every 60 seconds
├── Error rate calculation: every 60 seconds
├── Queue depth check: every 60 seconds
└── Memory usage check: every 60 seconds

PERIODIC REVIEWS:
├── 5-minute aggregation: every 5 minutes
├── 15-minute aggregation: every 15 minutes
├── Hourly report: every hour
├── Daily summary: 00:00 UTC
└── Weekly analysis: Monday 00:00 UTC
```

---

## 8. Stage 6: UPGRADE

### 8.1 Upgrade Triggers

| Trigger Type | Description | Priority |
|-------------|-------------|----------|
| **Capability Enhancement** | New features requested | Medium |
| **Performance Optimization** | Latency or throughput issue | High |
| **Security Patch** | Vulnerability fix required | Critical |
| **Compliance Update** | Policy or regulation change | High |
| **MCP Version Bump** | Dependent MCP updated | Medium |
| **Tier Promotion** | Agent demonstrates higher capability | Low |

### 8.2 Upgrade Workflow

```
Upgrade Request
      │
      ▼
Impact Assessment
(Does it affect other agents? Does it change behavior?)
      │
      ├── No Impact → Fast Track ──→ Approval (Ops Lead) ──→ Deploy
      │
      └── Impact → Full Review ──→ Approval (Architect + Ops Lead) ──→ Staged Deploy
```

### 8.3 Version Management

```yaml
semantic_versioning:
  format: "MAJOR.MINOR.PATCH"
  major:
    description: "Breaking changes to capabilities or behavior"
    requires: "Full regression testing + Owner approval"
  minor:
    description: "New capabilities, backward compatible"
    requires: "Integration testing"
  patch:
    description: "Bug fixes, security patches"
    requires: "Automated test pass"
```

### 8.4 Upgrade Rollback

| Upgrade Type | Rollback Trigger | Rollback Time | Method |
|-------------|-----------------|---------------|--------|
| **Patch** | Test failure | < 5 min | Automated |
| **Minor** | Post-deploy anomaly | < 15 min | Automated |
| **Major** | Post-deploy anomaly | < 60 min | Manual + Ops Lead |

---

## 9. Stage 7: SUSPEND

### 9.1 Suspension Triggers

| Category | Trigger | Auto/Manual |
|---------|---------|-------------|
| **Security** | Suspicious activity detected | Auto |
| **Security** | Policy violation confirmed | Manual |
| **Performance** | Resource exhaustion | Auto |
| **Performance** | Continuous degradation | Manual |
| **Operational** | Human request (vacation, etc.) | Manual |
| **Compliance** | Audit or investigation | Manual |
| **Financial** | Budget exceeded | Auto |

### 9.2 Suspension Procedure

```
SUSPEND REQUEST RECEIVED: {agent_id}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
IMMEDIATE ACTIONS:
  [ ] Stop accepting new tasks (drain queue gracefully)
  [ ] Complete in-flight tasks (max 5 minutes)
  [ ] Save state to persistent storage
  [ ] Close MCP connections (graceful)
  [ ] Update registry status → SUSPENDED
  [ ] Emit suspension event

NOTIFICATIONS:
  [ ] Owner notified
  [ ] Operations log updated
  [ ] Monitoring dashboard updated
  [ ] Audit log entry created

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SUSPENDED BY: {human_id|system_trigger}
SUSPENSION REASON: {reason_code}
SUSPENSION TIME: {ISO8601}
```

### 9.3 Reactivation Requirements

Before a suspended agent can be reactivated:

- [ ] Root cause of suspension resolved
- [ ] Owner approval obtained
- [ ] Security review passed (if security-related)
- [ ] Capability verification completed
- [ ] Monitoring re-enabled

---

## 10. Stage 8: ARCHIVE

### 10.1 Archive Triggers

| Trigger | Description | Grace Period |
|---------|-------------|--------------|
| **Inactivity** | No tasks for 90 days | 30-day warning |
| **Deprecation** | Agent type deprecated | 60-day notice |
| **Reorganization** | Office restructure | Variable |
| **Successor Ready** | Replacement agent active | Immediate |

### 10.2 Archive Procedure

```
ARCHIVE REQUEST: {agent_id}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
PRE-ARCHIVE:
  [ ] Final task completion
  [ ] State export to cold storage
  [ ] History preservation (90 days online, then cold)
  [ ] Owner final review completed

ARCHIVE:
  [ ] Update registry status → ARCHIVED
  [ ] Disable all MCP connections
  [ ] Remove from active monitoring
  [ ] Archive audit logs with agent state
  [ ] Notify all dependent agents

POST-ARCHIVE:
  [ ] Agent ID reserved (never reused)
  [ ] Historical data accessible for audit
  [ ] Runbook reference updated

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ARCHIVED BY: {human_id}
ARCHIVE REASON: {reason_code}
```

### 10.3 Archive Retention

| Data Type | Hot Storage | Cold Storage | Total |
|-----------|------------|--------------|-------|
| Task History | 90 days | 7 years | 7 years |
| Audit Logs | 90 days | 7 years | 7 years |
| State Snapshots | 30 days | 1 year | 1 year |
| Communication Logs | 90 days | 3 years | 3 years |

---

## 11. Stage 9: RETIRE

### 11.1 Retirement Triggers

- Agent declared obsolete with no successor
- Compliance/regulatory requirement
- Permanent security risk
- Business unit closure

### 11.2 Retirement Checklist

```
RETIRE REQUEST: {agent_id}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RETIREMENT APPROVAL: {Executive Office + Legal}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DATA HANDLING:
  [ ] All data exported per compliance
  [ ] PII data purged per GDPR/KVKK
  [ ] Credentials revoked
  [ ] Encryption keys rotated

REGISTRY:
  [ ] Status → RETIRED
  [ ] Retirement date recorded
  [ ] Successor reference (if any)
  [ ] Reason code documented

MONITORING:
  [ ] All monitoring disabled
  [ ] All alerts cleared
  [ ] Dashboard entries removed
  [ ] Metrics retention policy applied

VERIFICATION:
  [ ] No active tasks
  [ ] No pending requests
  [ ] No dependencies
  [ ] Owner sign-off received

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RETIRED BY: {human_id}
RETIREMENT DATE: {ISO8601}
FINAL STATE: {state_dump_reference}
```

---

## 12. Agent Registry

### 12.1 Registry Operations

```bash
# Register new agent
yalihan-cli agent register --file agent-creation-request.yaml

# Activate agent
yalihan-cli agent activate --agent-id {agent_id} --approved-by {human_id}

# Suspend agent
yalihan-cli agent suspend --agent-id {agent_id} --reason {code} --approved-by {human_id}

# Archive agent
yalihan-cli agent archive --agent-id {agent_id} --reason {code} --approved-by {human_id}

# Retire agent
yalihan-cli agent retire --agent-id {agent_id} --approval {approval-id} --executed-by {human_id}

# List agents by status
yalihan-cli agent list --status {status} --office {office}

# Get agent details
yalihan-cli agent get --agent-id {agent_id}

# Check agent health
yalihan-cli agent health --agent-id {agent_id}
```

### 12.2 Registry State Transitions

```
VALID TRANSITIONS:
registered   → active        (via activate)
active      → suspended      (via suspend)
active      → archived       (via archive)
suspended   → active         (via reactivate)
suspended   → archived       (via archive)
archived    → active         (via reactivate)
archived    → retired        (via retire)

INVALID TRANSITIONS:
registered  → retired        (MUST go through full lifecycle)
active      → retired        (MUST archive first)
retired     → active         (IMPOSSIBLE — retired is terminal)
```

---

## 13. Compliance & Audit

### 13.1 Required Audit Events

Every lifecycle transition MUST log:

```json
{
  "event_id": "uuid",
  "event_type": "AGENT_{STAGE}_TRANSITION",
  "agent_id": "string",
  "from_state": "string",
  "to_state": "string",
  "triggered_by": "human_id|system",
  "approver_id": "human_id|null",
  "reason": "string",
  "metadata": {},
  "timestamp": "ISO8601",
  "ip_address": "string",
  "user_agent": "string"
}
```

### 13.2 Retention Requirements

| Log Type | Retention | Storage |
|----------|-----------|---------|
| Lifecycle Transitions | 7 years | Cold + Encrypted |
| Task History | 7 years | Cold + Encrypted |
| Performance Metrics | 2 years | Warm |
| Error Logs | 2 years | Warm |
| Audit Logs | 7 years | Cold + Encrypted |

---

**Document Owner:** Operations Office  
**Review Cycle:** Quarterly  
**Related:** OPERATIONS_MANUAL.md, INCIDENT_MANAGEMENT.md  

---

*End of Agent Lifecycle Management*
