# YALIHAN OS — Incident Management

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Parent:** OPERATIONS_MANUAL.md  
**Date:** 2026-06-28  

---

## 1. Overview

This document defines the incident management framework for YALIHAN OS. Every incident must follow this process from detection to resolution and post-incident review.

The goal is to minimize user impact, restore service quickly, and learn from every incident to prevent recurrence.

---

## 2. Severity Classification

### 2.1 Severity Levels

| Severity | Definition | Impact | Examples |
|----------|-----------|--------|----------|
| **P0 — Critical** | Complete service outage or data loss | All users affected | API down, database unavailable, security breach |
| **P1 — High** | Major feature unavailable | Significant user impact | Core feature broken, >25% degradation |
| **P2 — Medium** | Minor feature impact | Limited user impact | Non-critical feature broken, performance issue |
| **P3 — Low** | Minimal impact | Few users affected | Cosmetic issue, minor bug |

### 2.2 Severity Determination Matrix

```
IMPACT SCOPE
     │
     │   ALL USERS    │   GROUP      │   INDIVIDUAL  │   SELF
─────┼────────────────┼──────────────┼───────────────┼──────────
     │                │              │               │
FATAL│     P0         │     P0       │     P1        │    P1
     │                │              │               │
MAJOR │     P0         │     P1       │     P2        │    P2
     │                │              │               │
MINOR │     P1         │     P2       │     P3        │    P3
     │                │              │               │
COSMETIC│    P2         │     P3       │     P3        │    P3
     │                │              │               │
```

### 2.3 Response Time SLAs

| Severity | First Response | Resolution Target | Escalation After |
|----------|---------------|------------------|------------------|
| **P0** | 5 minutes | 1 hour | 5 minutes |
| **P1** | 15 minutes | 4 hours | 30 minutes |
| **P2** | 1 hour | 24 hours | 2 hours |
| **P3** | 4 hours | 72 hours | 8 hours |

---

## 3. Incident Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│                        INCIDENT LIFECYCLE                            │
├─────────────┬─────────────┬─────────────┬─────────────┬─────────────┤
│  DETECTED   │   TRIAGED   │   ACTIVE    │  RESOLVED   │  CLOSED    │
│             │             │             │             │             │
│ Monitoring  │ Severity    │ Fix in      │ Service     │ Postmortem │
│ Alert       │ Assigned    │ Progress    │ Restored    │ Complete   │
│ User Report │ IC Assigned │ Communica-  │ Customer    │ Lessons    │
│ Automated   │ Communit-  │ tions Sent  │ Notified    │ Documented │
│             │ cations     │             │             │             │
└─────────────┴─────────────┴─────────────┴─────────────┴─────────────┘
```

---

## 4. Incident Response Procedures

### 4.1 P0 — Critical Incident

```bash
# P0 INCIDENT DETECTED
# IMMEDIATE ACTIONS (First 5 minutes):

# Step 1: Acknowledge alert
./scripts/ack-alert.sh --alert-id {alert_id} --ic {your_name}

# Step 2: Create incident record
./scripts/create-incident.sh \
    --severity P0 \
    --title "Brief description" \
    --ic {incident_commander} \
    --type {OUTAGE|DATA_LOSS|SECURITY}

# Step 3: Page on-call (if not already notified)
./scripts/page-oncall.sh --severity P0 --message "P0 incident declared"

# Step 4: Activate incident bridge
./scripts/activate-incident-bridge.sh --incident-id {incident_id}

# Step 5: Initial assessment (5 min)
# - What is broken?
# - Who is affected?
# - What is the business impact?
# - What is the current status?

# Step 6: Communicate status
./scripts/notify-status.sh --status INVESTIGATING
# Send to: All users, stakeholders, executive office

# Step 7: Begin recovery (parallel with Step 5)
./scripts/initiate-recovery.sh --type {recovery_type}
```

### 4.2 P1 — High Severity Incident

```bash
# P1 INCIDENT DETECTED

# Step 1: Acknowledge alert
./scripts/ack-alert.sh --alert-id {alert_id} --ic {your_name}

# Step 2: Create incident record
./scripts/create-incident.sh \
    --severity P1 \
    --title "Brief description" \
    --ic {incident_commander}

# Step 3: Notify on-call (15 min SLA)
./scripts/notify-oncall.sh --severity P1

# Step 4: Initial assessment (15 min)
./scripts/assess-impact.sh --incident-id {incident_id}

# Step 5: Communicate to affected users
./scripts/notify-users.sh --scope {all|group|individual}

# Step 6: Investigate root cause
./scripts/investigate.sh --incident-id {incident_id}

# Step 7: Deploy fix or workaround
./scripts/deploy-fix.sh --incident-id {incident_id}
```

### 4.3 P2 — Medium Severity Incident

```bash
# P2 INCIDENT DETECTED

# Step 1: Acknowledge alert (within 1 hour)
./scripts/ack-alert.sh --alert-id {alert_id}

# Step 2: Create incident (optional for P2, may be handled as ticket)
./scripts/create-incident.sh --severity P2 --title "Description"

# Step 3: Assign to appropriate team
./scripts/assign-incident.sh --incident-id {incident_id} --team {team}

# Step 4: Investigate during business hours
./scripts/investigate.sh --incident-id {incident_id}

# Step 5: Deploy fix in next maintenance window OR immediately if critical
```

### 4.4 P3 — Low Severity Incident

```bash
# P3 INCIDENT DETECTED

# Create ticket (not full incident)
./scripts/create-ticket.sh \
    --title "Description" \
    --severity P3 \
    --team {team}

# Prioritize in sprint planning
# Resolve within 72 hours SLA
```

---

## 5. Incident Commander Role

### 5.1 IC Responsibilities

| Phase | IC Responsibilities |
|-------|-------------------|
| **Detection** | Acknowledge and confirm the incident |
| **Triage** | Assess severity, assign resources |
| **Active** | Coordinate response, communicate status |
| **Resolution** | Verify fix, confirm service restoration |
| **Closure** | Ensure postmortem scheduled |

### 5.2 IC Checklist

```
INCIDENT COMMANDER CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
INCIDENT: {incident_id}
SEVERITY: {severity}
IC: {your_name}
TIME: {timestamp}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IMMEDIATE:
  [ ] Alert acknowledged
  [ ] Incident created
  [ ] Severity confirmed
  [ ] On-call notified (P0/P1)
  [ ] Incident bridge activated (P0)

ASSESSMENT:
  [ ] Scope identified
  [ ] Impact assessed
  [ ] Resources assigned
  [ ] Fix strategy defined

COMMUNICATION:
  [ ] Status page updated
  [ ] Users notified (if required)
  [ ] Executive notified (P0/P1)
  [ ] Team updates scheduled

RESOLUTION:
  [ ] Fix deployed
  [ ] Service restored
  [ ] Monitoring confirmed
  [ ] Status page updated

CLOSURE:
  [ ] Incident resolved
  [ ] Postmortem scheduled
  [ ] Action items created
  [ ] Incident closed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## 6. Escalation Matrix

### 6.1 Escalation Path

```
LEVEL 1: On-Call Engineer
  Time: Immediate
  Scope: Initial response, initial triage
  Contact: PagerDuty → On-Call

LEVEL 2: Operations Lead
  Time: P0: 5min, P1: 30min, P2: 2hr, P3: 8hr
  Scope: Resource coordination, cross-team issues
  Contact: Direct → Operations Lead

LEVEL 3: Architect on Call
  Time: P0: 15min, P1: 1hr, P2: 4hr
  Scope: Technical decisions, design changes
  Contact: Operations Lead → Architect

LEVEL 4: Operations Director
  Time: P0: 30min, P1: 2hr
  Scope: Major incidents, executive communication
  Contact: Escalation from Operations Lead

LEVEL 5: Executive Office
  Time: P0: 1hr (if not resolved)
  Scope: Board-level incidents, PR issues
  Contact: Operations Director → Executive
```

### 6.2 Escalation Triggers

| Trigger | Action |
|---------|--------|
| P0 not acknowledged in 5 min | Auto-escalate to Operations Lead |
| P0 not resolved in 30 min | Escalate to Operations Director |
| P0 not resolved in 1 hour | Escalate to Executive Office |
| Customer data at risk | Immediate escalation to Security |
| Security breach suspected | Immediate escalation to Security Officer |
| Third-party service involved | Notify Integration Office Lead |

---

## 7. Communication Protocols

### 7.1 Communication Templates

**P0 — Initial Notification:**

```
SUBJECT: [P0] {Service Name} — Service Outage — {Time}

INCIDENT: {incident_id}
SEVERITY: P0 — Critical
STATUS: Investigating
STARTED: {timestamp}
AFFECTED: {scope}

WHAT WE KNOW:
{current status}

IMPACT:
{user/business impact}

NEXT UPDATE: In 15 minutes
INCIDENT COMMANDER: {name}
BRIDGE: {link}
STATUS PAGE: {link}
```

**P0 — Resolution:**

```
SUBJECT: [RESOLVED] [P0] {Service Name} — {Time}

INCIDENT: {incident_id}
RESOLVED: {timestamp}
DURATION: {duration}
SEVERITY: P0 — Critical

ROOT CAUSE:
{brief description}

RESOLUTION:
{what was done to fix}

NEXT STEPS:
- Postmortem scheduled for {date}
- Action items being tracked
```

### 7.2 Communication Channels

| Audience | Channel | Frequency |
|----------|---------|-----------|
| All users | Status page, Email | Initial + Resolution |
| Affected users | Email, In-app | Initial + Updates |
| Executive Office | Direct message | P0: 15min, P1: 1hr |
| Internal teams | Slack #incidents | Continuous |
| External parties | As required | Per policy |

---

## 8. Root Cause Analysis

### 8.1 RCA Process

```
INCIDENT RESOLVED
      │
      ▼
┌───────────────────────────┐
│ 1. EVIDENCE COLLECTION     │
│    - Logs preserved        │
│    - Metrics captured      │
│    - Timeline constructed   │
│    - No changes to system  │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ 2. TIMELINE RECONSTRUCTION │
│    - What happened        │
│    - When it happened     │
│    - Who was involved     │
│    - What actions taken   │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ 3. ROOT CAUSE IDENTIFIED   │
│    - Why it happened      │
│    - Contributing factors │
│    - Systemic vs isolated │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ 4. ACTION ITEMS           │
│    - Prevent recurrence   │
│    - Improve detection    │
│    - Reduce impact        │
│    - Assign owners        │
│    - Set deadlines        │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ 5. POSTMORTEM REVIEW      │
│    - Present findings     │
│    - Get consensus        │
│    - Finalize actions    │
│    - Update documentation │
└───────────────────────────┘
```

### 8.2 RCA Template

```yaml
postmortem:
  incident_id: string
  title: string
  date: ISO8601
  severity: P0|P1|P2|P3
  duration: duration
  impact: string

  timeline:
    - time: ISO8601
      event: string
    - time: ISO8601
      event: string

  root_cause:
    primary: string
    contributing_factors:
      - string

  impact:
    users_affected: number
    revenue_impact: currency
    reputation_impact: string

  action_items:
    - id: string
      description: string
      type: prevention|detection|mitigation
      owner: string
      deadline: ISO8601
      status: open|in_progress|completed

  lessons_learned:
    what_went_well: [string]
    what_could_be_improved: [string]
    surprises: [string]

  signatures:
    - role: string
      name: string
      date: ISO8601
```

---

## 9. Postmortem Requirements

### 9.1 Postmortem SLAs

| Severity | Postmortem Due | Review Audience |
|----------|---------------|----------------|
| P0 | 48 hours after resolution | All hands + Executive |
| P1 | 5 business days | Operations + Affected teams |
| P2 | 2 weeks | Team level |
| P3 | Optional | Team level |

### 9.2 Postmortem Distribution

```
Distribution List:
  - All employees
  - Executive Office (P0, P1)
  - Architecture Office (technical issues)
  - Product Office (feature issues)
  - Customer Success (P0, P1)

Archive:
  - Incident management system (permanent)
  - Knowledge base (reference)
  - Quarterly review (trends)
```

---

## 10. Incident Metrics

### 10.1 Key Metrics

| Metric | Definition | Target |
|--------|-----------|--------|
| **MTTR** | Mean Time To Resolution | P0: <1hr, P1: <4hr |
| **MTTD** | Mean Time To Detect | < 5 min |
| **MTTA** | Mean Time To Acknowledge | P0: <5min, P1: <15min |
| **Incident Rate** | Incidents per period | Declining trend |
| **Repeat Incidents** | Same root cause twice | 0 per quarter |
| **Postmortem Completion** | % with completed postmortem | 100% |

### 10.2 Monthly Incident Report

```yaml
monthly_incident_report:
  month: YYYY-MM
  total_incidents: number
  by_severity:
    P0: number
    P1: number
    P2: number
    P3: number
  total_downtime: duration
  mttr_average:
    P0: duration
    P1: duration
    P2: duration
  top_root_causes:
    - cause: string
      count: number
  action_items_tracking:
    open: number
    completed: number
    overdue: number
```

---

**Document Owner:** Operations Office  
**Review Cycle:** Monthly  
**Related:** OPERATIONS_MANUAL.md, ESCALATION_POLICY.md, SLA_AND_SLO.md  

---

*End of Incident Management*
