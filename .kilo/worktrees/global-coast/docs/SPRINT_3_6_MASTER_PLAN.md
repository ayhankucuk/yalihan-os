# SPRINT 3.6 MASTER PLAN

**Epic: "Platform Core Foundation"**  
**Sprint Duration: ~22 days**  
**Start Date: 2026-06-28**  
**Theme: Build the Event-Driven Agent Platform**

---

## OVERVIEW

Sprint 3.6 transforms the AI Workforce design into working code. The sprint establishes the event-driven architecture that enables all 15 agents to communicate through Hermes Core.

### Definition of Done
- All events flow through Hermes Core
- All 4 agents respond to their respective events
- Corporate Ontology canonical names in use
- Standard Agent Contract implemented
- Escalation workflow operational

---

## EPIC 1: Corporate Ontology

**Priority: P0 — Blocking**  
**Theme: Establish the single source of truth for entity names**

### Story 1.1: Finalize Canonical Entity Definitions
```
AS: Platform Engineer
I WANT: All entity names to follow consistent canonical naming
SO THAT: No naming conflicts across services

TASKS:
  □ Review all entity definitions (Kilo)
  □ Resolve conflicts between Knowledge Office and Architecture Office
  □ Publish canonical names to authority.json
  □ Update all existing services to use canonical names

ACCEPTANCE CRITERIA:
  - authority.json contains all canonical entity names
  - No service uses non-canonical names
  - All naming follows pattern: Turkish for domain, English for technical

ESTIMATED: 1 day
```

---

## EPIC 2: Hermes Core

**Priority: P0 — Blocking**  
**Theme: Build the event-driven communication backbone**

### Story 2.1: Implement Event Bus Infrastructure
```
AS: Platform Engineer
I WANT: Events to flow through a central bus
SO THAT: Agents can subscribe and respond to platform events

TASKS:
  □ Create Laravel Event Service Provider
  □ Define event schema classes (ilan.created, photo.uploaded, etc.)
  □ Set up Redis event transport layer
  □ Create event logging and tracing

ACCEPTANCE CRITERIA:
  - Events publish to Redis queue
  - Events are logged with correlation IDs
  - Events can be traced from publish to consumption

ESTIMATED: 3 days
```

### Story 2.2: Implement Hermes Routing
```
AS: Platform Engineer
I WANT: Events to route to the correct agents
SO THAT: Agents respond only to their subscribed events

TASKS:
  □ Create Hermes orchestrator class
  □ Implement event subscription system
  □ Create agent registry service
  □ Implement escalation workflow logic

ACCEPTANCE CRITERIA:
  - Hermes routes events to subscribed agents
  - Agents are registered before receiving events
  - Escalation events route to Telegram

ESTIMATED: 4 days
```

---

## EPIC 3: First 4 Agents

**Priority: P0 — Core Deliverable**  
**Theme: Implement the first 4 platform agents**

### Story 3.1: Portfolio Agent
```
AS: Property Manager
I WANT: Portfolio agent to track all portfolio changes
SO THAT: Analytics stay current automatically

TASKS:
  □ Create agent class extending Standard Agent Contract
  □ Implement portfolio.created handler
  □ Implement portfolio.updated handler
  □ Implement analytics cache invalidation

ACCEPTANCE CRITERIA:
  - Responds to portfolio events within 500ms
  - Updates analytics cache on portfolio change
  - Logs all portfolio event processing

ESTIMATED: 2 days
```

### Story 3.2: Photo Agent
```
AS: Content Manager
I WANT: Photo agent to process uploaded photos
SO THAT: Listings have optimized images automatically

TASKS:
  □ Create agent class extending Standard Agent Contract
  □ Implement photo.uploaded handler
  □ Integrate with existing IlanPhotoService
  □ Implement thumbnail generation and optimization

ACCEPTANCE CRITERIA:
  - Processes photos on upload event
  - Generates multiple sizes (thumb, medium, large)
  - Updates listing with processed photo URLs

ESTIMATED: 2 days
```

### Story 3.3: Description Agent
```
AS: Marketing Manager
I WANT: Description agent to generate listing descriptions
SO THAT: Listings have professional AI-generated content

TASKS:
  □ Create agent class extending Standard Agent Contract
  □ Integrate with YalihanCortex for generation
  □ Implement multi-language support (TR, EN, RU)
  □ Implement description quality scoring

ACCEPTANCE CRITERIA:
  - Generates description when listing reaches readiness
  - Supports Turkish, English, Russian
  - Scores description quality > 8/10

ESTIMATED: 3 days
```

### Story 3.4: Notification Agent
```
AS: Operations Manager
I WANT: Notification agent to alert stakeholders
SO THAT: Important events are never missed

TASKS:
  □ Create agent class extending Standard Agent Contract
  □ Integrate Telegram bot for notifications
  □ Implement escalation notification workflow
  □ Create notification templates

ACCEPTANCE CRITERIA:
  - Sends Telegram notifications on escalation
  - Includes relevant context in message
  - Supports escalation acknowledgment

ESTIMATED: 2 days
```

---

## EPIC 4: Standard Agent Contract

**Priority: P1 — Infrastructure**  
**Theme: Establish the contract all agents must follow**

### Story 4.1: Implement Base Agent Class
```
AS: Platform Engineer
I WANT: All agents to extend a common base class
SO THAT: Agent behavior is consistent and monitorable

TASKS:
  □ Create Agent abstract class
  □ Implement Standard Agent Contract interface
  □ Create agent registry service
  □ Implement health monitoring methods

ACCEPTANCE CRITERIA:
  - All 4 agents extend base Agent class
  - Agents implement required contract methods
  - Health status is queryable

ESTIMATED: 2 days
```

---

## EPIC 5: AI Workforce Dashboard

**Priority: P1 — Observability**  
**Theme: Provide visibility into agent operations**

### Story 5.1: Dashboard Specification
```
AS: Operations Manager
I WANT: Real-time view of all agent activity
SO THAT: I can monitor platform health

TASKS:
  □ Create dashboard wireframe
  □ Define real-time metrics (events/sec, latency, errors)
  □ Design agent status cards
  □ Specify data refresh intervals

ACCEPTANCE CRITERIA:
  - Wireframe shows all 4 agents
  - Metrics are clearly defined
  - Design approved by Operations Office

ESTIMATED: 1 day
```

---

## EPIC 6: Integration

**Priority: P1 — Connectors**  
**Theme: Connect external workflows to agent platform**

### Story 6.1: n8n Workflow Connectors
```
AS: Integration Engineer
I WANT: n8n workflows to trigger agent actions
SO THAT: External automations integrate with the platform

TASKS:
  □ Create n8n webhook handler endpoints
  □ Implement workflow trigger events
  □ Test end-to-end flows with sample workflows
  □ Document webhook API

ACCEPTANCE CRITERIA:
  - n8n can trigger any agent via webhook
  - Webhook events flow through Hermes Core
  - End-to-end test passes

ESTIMATED: 2 days
```

---

## SPRINT SUMMARY

| Epic | Stories | Days | Status |
|------|---------|------|--------|
| 1. Corporate Ontology | 1 | 1 | P0 |
| 2. Hermes Core | 2 | 7 | P0 |
| 3. First 4 Agents | 4 | 9 | P0 |
| 4. Standard Agent Contract | 1 | 2 | P1 |
| 5. AI Workforce Dashboard | 1 | 1 | P1 |
| 6. Integration | 1 | 2 | P1 |
| **TOTAL** | **10** | **~22** | |

---

## SPRINT BOARD

```
TO DO                          IN PROGRESS                    DONE
─────────────────────────────   ────────────────────────────   ──────────────────────────────
□ Corporate Ontology           □ Hermes Core                  □ (none yet)
□ Event Bus Infrastructure     □ First 4 Agents
□ Agent Personnel Files        □ Standard Agent Contract
□ Dashboard Spec
□ Event Catalog
□ n8n Connectors
```

---

## ROLLING 6-ITEM WIP LIMIT

1. Corporate Ontology
2. Event Bus Infrastructure
3. First 4 Agents
4. Standard Agent Contract
5. Dashboard Spec
6. n8n Connectors

*No new work until one item completes.*

---

## EXIT CRITERIA

- [ ] All P0 stories in DONE column
- [ ] All 4 agents respond to events
- [ ] Hermes Core routes events correctly
- [ ] Event logging captures all activity
- [ ] Corporate Ontology canonical names in use
- [ ] Standard Agent Contract implemented

---

*This plan is the single source of truth for Sprint 3.6 execution.*