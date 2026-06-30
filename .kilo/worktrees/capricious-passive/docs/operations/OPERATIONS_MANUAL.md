# YALIHAN OS — Operations Manual

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Date:** 2026-06-28  
**Classification:** Internal — Enterprise  

---

## 1. Purpose & Scope

This Operations Manual defines the complete operational model for YALIHAN OS AI Corporation — a multi-agent enterprise platform targeting 500+ digital workers, operating across multi-agent, multi-MCP, and human-AI collaboration paradigms.

This manual is the **SSOT (Single Source of Truth)** for day-to-day operations. It complements the Architecture Office deliverables (ADR-041, system design) and Business Office strategy.

---

## 2. Organizational Structure

### 2.1 Office Hierarchy

```
EXECUTIVE OFFICE
    └── OPERATIONS OFFICE (This Document)
          ├── Agent Operations
          ├── Workflow Orchestration
          ├── Monitoring & Alerting
          ├── Incident Management
          ├── Queue & Retry Management
          ├── SLA Management
          └── Disaster Recovery
```

### 2.2 Supporting Offices

| Office | Role | Operations Interface |
|--------|------|---------------------|
| **Architecture Office** | System design, ADR decisions | Provides design specs; Operations references |
| **Business Office** | Strategy, roadmap, priorities | Sets operational SLAs and KPIs |
| **Research Office** | Innovation, pattern research | Proposes new operational capabilities |
| **Product Office** | Feature delivery | Issues operational change requests |
| **Integration Office** | MCP, API, third-party integration | Monitors integration health |
| **Knowledge Office** | Documentation, wikis, memory | Maintains operational knowledge base |

---

## 3. Target Platform Scale

| Dimension | Target |
|-----------|--------|
| Digital Workers | 500+ |
| Concurrent Agents | 200+ |
| MCP Servers | 15–25 |
| Vector DB Collections | 50+ |
| Daily Tasks Processed | 50,000+ |
| Concurrent Sessions | 1,000+ |
| Availability Target | 99.9% |
| Data Residency | Bodrum, Turkey (primary) |

---

## 4. Operational Model

### 4.1 Core Principle: Human-in-the-Loop

Every critical operation requires human oversight:

```
Task Request → AI Agent Processing → Human Approval → Execution
                    ↑                        ↓
                    ←←←←←←  Review Gate  ←←←←←←
```

### 4.2 Agent Authority Model

| Agent Tier | Authority | Human Approval |
|------------|-----------|----------------|
| **Strategic** (Executive) | Full autonomy within policy | None required |
| **Tactical** (Senior) | Autonomous execution | Required for high-risk |
| **Operational** (Standard) | Routine task execution | Required for writes |
| **Limited** (Junior) | Simple, repeatable tasks | Always required |

### 4.3 Multi-Agent Coordination

```
┌─────────────────────────────────────────────────────────┐
│                    Executive Office                       │
│              (Strategic Decision Layer)                   │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│               Operations Office                          │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │
│  │ Agent   │  │ Queue   │  │ Monitor │  │ Incident│  │
│  │ Manager │  │ Manager │  │         │  │ Manager │  │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘  │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│               Execution Layer                             │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │
│  │ Product │  │Integr.  │  │Knowledge│  │Research │  │
│  │ Office  │  │ Office  │  │ Office  │  │ Office  │  │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 5. Daily Operations

### 5.1 Daily Startup Sequence

```
TIME       ACTION                      AUTOMATION    HUMAN
--------   --------------------------  -----------   -----
00:00      Health monitoring begins    ✅            —
06:00      Queue cleanup job           ✅            —
07:00      Memory maintenance           ✅            —
08:00      Agent pool health check      ✅            —
08:30      Daily operations briefing    ✅            📋
09:00      Human oversight review       —            ✅
09:30      Platform open for business   ✅            📋
```

### 5.2 Daily Shutdown Sequence

```
TIME       ACTION                      AUTOMATION    HUMAN
--------   --------------------------  -----------   -----
18:00      Queue drain (graceful)     ✅            —
18:30      Task completion report      ✅            —
19:00      Session state persistence   ✅            —
19:30      Monitoring alert tuning     ✅            —
20:00      Daily incident review       —            ✅
20:30      Operations log archival     ✅            —
21:00      System maintenance window   ✅            📋
```

### 5.3 Health Verification Checklist

- [ ] All agent pools report healthy status
- [ ] Queue depths within SLA thresholds
- [ ] MCP server connectivity verified
- [ ] Vector DB cluster healthy
- [ ] Token usage within daily budget
- [ ] No P0/P1 incidents active
- [ ] Backup jobs completed successfully
- [ ] Memory usage < 80% on all workers

---

## 6. Agent Identity Management

### 6.1 Agent Registration

Every agent must be registered before activation:

```
Agent Registry Entry:
{
  "agent_id": "unique-identifier",
  "office": "product|research|integration|knowledge|operations",
  "tier": "strategic|tactical|operational|limited",
  "capabilities": ["capability-1", "capability-2"],
  "mcp_scope": ["mcp-server-1", "mcp-server-2"],
  "owner": "human-owner-id",
  "created_at": "ISO8601",
  "status": "registered|active|suspended|archived"
}
```

### 6.2 Identity Lifecycle State Machine

```
                    ┌─────────────┐
                    │  REGISTERED │
                    └──────┬──────┘
                           │ activate
                    ┌──────▼──────┐
                    │   ACTIVE    │◄─────────┐
                    └──────┬──────┘         │
           │               │               │
           │ upgrade        │ suspend        │ reactivate
           ▼               ▼               │
    ┌──────────────┐  ┌───────────┐         │
    │  UPGRADED   │  │ SUSPENDED │─────────┘
    └──────┬───────┘  └─────┬─────┘
           │                │ archive
           │                ▼
           │          ┌───────────┐
           │          │  ARCHIVED │
           │          └─────┬─────┘
           │                │ retire
           ▼                ▼
    ┌─────────────────────────────┐
    │          RETIRED            │
    └─────────────────────────────┘
```

---

## 7. Operational SLAs

### 7.1 Platform SLAs

| Service | Availability | Response Time | Recovery Time |
|---------|-------------|---------------|---------------|
| API Gateway | 99.9% | < 200ms p99 | < 15 min |
| Agent Execution | 99.5% | < 30s p95 | < 30 min |
| MCP Servers | 99.7% | < 500ms p99 | < 10 min |
| Vector DB | 99.9% | < 100ms p99 | < 5 min |
| Knowledge Base | 99.5% | < 2s p99 | < 60 min |

### 7.2 Incident Response SLAs

| Severity | First Response | Resolution Target | Escalation |
|----------|---------------|------------------|------------|
| P0 | 5 min | 1 hour | Immediate |
| P1 | 15 min | 4 hours | 30 min |
| P2 | 1 hour | 24 hours | 2 hours |
| P3 | 4 hours | 72 hours | 8 hours |

---

## 8. Monitoring & Observability

### 8.1 The Four Golden Signals

| Signal | Description | SLO |
|--------|-------------|-----|
| **Latency** | Request response time | p99 < 500ms |
| **Traffic** | Requests per second | > 1000 RPS |
| **Errors** | Error rate | < 0.1% |
| **Saturation** | Resource utilization | < 80% |

### 8.2 Dashboards

1. **Executive Dashboard** — High-level KPIs, SLA compliance
2. **Operations Dashboard** — Real-time health, queues, incidents
3. **Agent Dashboard** — Per-agent metrics, utilization
4. **Cost Dashboard** — Token usage, infrastructure cost

---

## 9. Security & Compliance

### 9.1 Data Classification

| Level | Description | Handling |
|-------|-------------|----------|
| **Public** | Marketing, docs | No restrictions |
| **Internal** | Operational docs | Authenticated access |
| **Confidential** | Business strategy | Need-to-know basis |
| **Restricted** | Credentials, keys | Encrypted, audited |

### 9.2 Audit Requirements

- All agent actions logged with timestamp, agent ID, action, result
- Audit logs retained for 90 days (hot), 7 years (cold)
- Quarterly compliance review

---

## 10. Change Management

### 10.1 Operational Change Types

| Type | Description | Approval | Rollback |
|------|-------------|----------|----------|
| **Hotfix** | Emergency P0 fix | None (事后审批) | Immediate |
| **Patch** | Minor fix | Ops Lead | Automated |
| **Minor** | New feature | Architect + Ops | Planned |
| **Major** | Architecture change | CAB + Executive | Full DR |

### 10.2 Change Request Process

```
Request → Impact Assessment → Approval → Testing → Deploy → Verify
   │              │                 │         │        │         │
   │              ▼                 ▼         ▼        ▼         ▼
  User       Operations         CAB       CI/CD   Canary    Monitor
```

---

## 11. Operational Contacts

| Role | Responsibility | Escalation Target |
|------|---------------|-------------------|
| **Operations Lead** | Day-to-day ops | Executive Office |
| **On-Call Engineer** | P0/P1 incidents | Operations Lead |
| **Architect on Call** | Design decisions | Executive Office |
| **Security Officer** | Security incidents | Executive Office |

---

## 12. Document References

| Reference | Document |
|-----------|----------|
| Architecture | `docs/SAB.md`, `docs/adr/ADR-041.md` |
| Agent Lifecycle | `docs/operations/AGENT_LIFECYCLE.md` |
| Incident Mgmt | `docs/operations/INCIDENT_MANAGEMENT.md` |
| Monitoring | `docs/operations/MONITORING_AND_ALERTING.md` |
| SLA | `docs/operations/SLA_AND_SLO.md` |
| Queue Policy | `docs/operations/QUEUE_AND_RETRY_POLICY.md` |
| Escalation | `docs/operations/ESCALATION_POLICY.md` |
| Disaster Recovery | `docs/operations/DISASTER_RECOVERY.md` |

---

**Document Owner:** Operations Office  
**Review Cycle:** Monthly  
**Next Review:** 2026-07-28  

---

*End of Operations Manual*
