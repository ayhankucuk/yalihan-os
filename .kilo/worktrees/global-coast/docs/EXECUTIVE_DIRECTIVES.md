# EXECUTIVE DIRECTIVES

**Sprint 3.6 — Implementation Phase**  
**Date: 2026-06-28**  
**Effective: Immediately**

---

## P0 DIRECTIVES — MUST BUILD IMMEDIATELY

### Directive 1: Corporate Ontology Finalization

| Field | Value |
|-------|-------|
| **Priority** | P0 — Critical Path |
| **Owner** | Knowledge Office |
| **Expected Deliverable** | Canonical entity definitions for all platform domains |
| **Dependencies** | None (blocking all other work) |
| **Success Metric** | All code references canonical names; no naming conflicts |
| **Acceptance Criteria** | `authority.json` updated, all services use canonical names |

### Directive 2: Hermes Core Implementation

| Field | Value |
|-------|-------|
| **Priority** | P0 — Critical Path |
| **Owner** | Architecture Office |
| **Expected Deliverable** | Event bus + routing engine for agent communication |
| **Dependencies** | Corporate Ontology |
| **Success Metric** | Events flow through system; agents respond to triggers |
| **Acceptance Criteria** | Hermes class operational, event subscriptions work |

### Directive 3: Event Bus Infrastructure

| Field | Value |
|-------|-------|
| **Priority** | P0 — Critical Path |
| **Owner** | Architecture Office |
| **Expected Deliverable** | Laravel event system + Redis transport layer |
| **Dependencies** | Hermes Core |
| **Success Metric** | All events traceable from publish to consumption |
| **Acceptance Criteria** | Events logged, routed, and delivered to agents |

### Directive 4: First 4 Agents Implementation

| Field | Value |
|-------|-------|
| **Priority** | P0 — Critical Path |
| **Owner** | Operations Office |
| **Expected Deliverable** | Portfolio, Photo, Description, Notification agents |
| **Dependencies** | Hermes Core, Standard Agent Contract |
| **Success Metric** | All 4 agents respond to events within 500ms |
| **Acceptance Criteria** | E2E test: portfolio event → photo processing → description generation → notification |

---

## P1 DIRECTIVES — BUILD DURING SPRINT 3.6

### Directive 5: Agent Personnel Files Digitalization

| Field | Value |
|-------|-------|
| **Priority** | P1 — High |
| **Owner** | Knowledge Office |
| **Expected Deliverable** | Database schema for agent HR records |
| **Dependencies** | Corporate Ontology |
| **Success Metric** | All 15 agents have digital personnel profiles |
| **Acceptance Criteria** | CRUD operations work for agent records |

### Directive 6: AI Workforce Dashboard Spec

| Field | Value |
|-------|-------|
| **Priority** | P1 — High |
| **Owner** | Operations Office |
| **Expected Deliverable** | Dashboard wireframe with real-time metrics |
| **Dependencies** | First 4 agents |
| **Success Metric** | Real-time agent status monitoring |
| **Acceptance Criteria** | Wireframe approved by Board |

### Directive 7: Standard Agent Contract Implementation

| Field | Value |
|-------|-------|
| **Priority** | P1 — High |
| **Owner** | Architecture Office |
| **Expected Deliverable** | Agent base class extending Standard Agent Contract |
| **Dependencies** | Hermes Core |
| **Success Metric** | All agents follow contract pattern |
| **Acceptance Criteria** | All 4 agents extend base class |

### Directive 8: Telegram Escalation Integration

| Field | Value |
|-------|-------|
| **Priority** | P1 — High |
| **Owner** | Operations Office |
| **Expected Deliverable** | Human approval workflow via Telegram bot |
| **Dependencies** | Hermes Core, Notification Agent |
| **Success Metric** | Escalations reach human reviewers within 30s |
| **Acceptance Criteria** | Telegram bot receives and displays escalation events |

### Directive 9: Event Catalog Implementation

| Field | Value |
|-------|-------|
| **Priority** | P1 — High |
| **Owner** | Architecture Office |
| **Expected Deliverable** | All event definitions documented in code |
| **Dependencies** | Event Bus |
| **Success Metric** | All events documented and searchable |
| **Acceptance Criteria** | Event schema definitions in code with examples |

---

## P2 DIRECTIVES — LATER

### Directive 10: Presentation Studio

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | Knowledge Office |
| **Expected Deliverable** | Multi-format content pipeline (PDF/Slides/Canva) |
| **Dependencies** | Knowledge Layer |
| **Success Metric** | Content exportable to 3+ formats |
| **Acceptance Criteria** | Property description → PDF/Slides/Canva ready |

### Directive 11: Market Intelligence Agents

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | Market Office |
| **Expected Deliverable** | Market Scanner + Price Analytics agents |
| **Dependencies** | Hermes Core |
| **Success Metric** | Market monitoring active 24/7 |
| **Acceptance Criteria** | Market events trigger analysis workflow |

### Directive 12: CRM Division Agents

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | CRM Office |
| **Expected Deliverable** | Lead Intake + Matching + Follow-up agents |
| **Dependencies** | First 4 agents |
| **Success Metric** | Lead workflow completes without manual intervention |
| **Acceptance Criteria** | Lead created → matched → followed up automatically |

### Directive 13: Channel Agent

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | Publishing Office |
| **Expected Deliverable** | Multi-channel publishing to Airbnb + Web |
| **Dependencies** | Hermes Core |
| **Success Metric** | Listings published to 2+ channels |
| **Acceptance Criteria** | Agent publishes to Airbnb API + Web CMS |

### Directive 14: Knowledge Graph

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | Knowledge Office |
| **Expected Deliverable** | SOP + Cross-reference system |
| **Dependencies** | NotebookLM Integration |
| **Success Metric** | Semantic search working across all SOPs |
| **Acceptance Criteria** | User can query "how to handle escalation" and get relevant SOPs |

### Directive 15: Corporate Memory Growth

| Field | Value |
|-------|-------|
| **Priority** | P2 — Medium |
| **Owner** | Knowledge Office |
| **Expected Deliverable** | Drive structure + 11 notebooks populated |
| **Dependencies** | Knowledge Agent |
| **Success Metric** | Memory expands with each Sprint |
| **Acceptance Criteria** | Corporate Memory grows by 10% per Sprint |

---

## IMPLEMENTATION NOTES

### Blocking Dependencies
```
Corporate Ontology
       ↓
   Hermes Core
       ↓
  Event Bus + Standard Agent Contract
       ↓
   First 4 Agents
       ↓
   P1/P2 Directives
```

### Resource Allocation
| Office | P0 Focus | Capacity |
|--------|----------|----------|
| Architecture Office | Hermes Core, Event Bus | 60% |
| Knowledge Office | Corporate Ontology | 25% |
| Operations Office | First 4 Agents | 15% |

---

*Directives are binding unless modified by Board resolution.*